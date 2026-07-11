# PCB Supplier RFQ Guide

## 1. Executive Summary

This guide defines the **NeoGiga Supplier RFQ System**, enabling automated and manual Request-for-Quotation workflows between NeoGiga engineers/customers and verified PCB manufacturers/suppliers.

**Core Principle:** Suppliers see only authorized project files. Competitors' quotes are never visible to other suppliers. All communication flows through NeoGiga to protect pricing intelligence.

## 2. Architecture Overview

### 2.1 RFQ Workflow
1. **Trigger:** Instant quote unavailable OR customer requests comparison.
2. **Package:** System bundles requirements, Gerber, BOM, CPL.
3. **Invite:** Selected suppliers notified via portal/email.
4. **NDA:** Supplier accepts digital NDA (if required).
5. **Access:** Temporary, expiring file access granted.
6. **Submit:** Supplier enters quote (price, lead time, notes).
7. **Compare:** NeoGiga normalizes quotes for customer review.
8. **Award:** Customer selects supplier; order created.

### 2.2 Integration Points
- **Existing:** `suppliers`, `seller_offers`, `notifications`, `messages`.
- **New:** `pcb_rfq_requests`, `pcb_rfq_quotes`, `pcb_rfq_invitations`, `pcb_supplier_access_logs`.

## 3. Database Schema

### 3.1 RFQ Requests
Parent record for a quotation request.

```sql
CREATE TABLE pcb_rfq_requests (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pcb_project_id UUID NOT NULL REFERENCES pcb_projects(id),
    created_by UUID REFERENCES users(id),
    
    rfq_type VARCHAR(50), -- 'fabrication', 'assembly', 'turnkey', 'design'
    status VARCHAR(50) DEFAULT 'draft', -- draft, open, closed, awarded, cancelled
    
    requirements_summary TEXT,
    target_price DECIMAL(12,2), -- Optional customer budget
    target_lead_time_days INT,
    currency CHAR(3) DEFAULT 'USD',
    
    deadline_at TIMESTAMP WITH TIME ZONE, -- Quote submission deadline
    awarded_quote_id UUID, -- References winning quote
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_rfq_project ON pcb_rfq_requests(pcb_project_id);
CREATE INDEX idx_rfq_status ON pcb_rfq_requests(status);
```

### 3.2 RFQ Invitations
Tracks which suppliers were invited.

```sql
CREATE TABLE pcb_rfq_invitations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    rfq_request_id UUID NOT NULL REFERENCES pcb_rfq_requests(id) ON DELETE CASCADE,
    supplier_id UUID NOT NULL REFERENCES suppliers(id), -- Or pcb_manufacturers
    
    status VARCHAR(50) DEFAULT 'pending', -- pending, accepted, declined, expired, no_response
    nda_required BOOLEAN DEFAULT FALSE,
    nda_accepted_at TIMESTAMP WITH TIME ZONE,
    nda_accepted_by UUID REFERENCES users(id),
    
    file_access_token VARCHAR(255), -- Signed token for secure file download
    file_access_expires_at TIMESTAMP WITH TIME ZONE,
    
    invited_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    responded_at TIMESTAMP WITH TIME ZONE,
    
    UNIQUE(rfq_request_id, supplier_id)
);

CREATE INDEX idx_rfq_inv_supplier ON pcb_rfq_invitations(supplier_id);
```

### 3.3 RFQ Quotes
Supplier submissions.

```sql
CREATE TABLE pcb_rfq_quotes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    rfq_invitation_id UUID NOT NULL REFERENCES pcb_rfq_invitations(id),
    
    -- Pricing Breakdown
    fabrication_cost DECIMAL(12,2),
    assembly_cost DECIMAL(12,2),
    component_cost DECIMAL(12,2),
    stencil_cost DECIMAL(12,2),
    testing_cost DECIMAL(12,2),
    engineering_charge DECIMAL(12,2),
    freight_cost DECIMAL(12,2),
    
    total_price DECIMAL(12,2),
    currency CHAR(3) DEFAULT 'USD',
    price_valid_until DATE,
    
    -- Production Details
    lead_time_days INT,
    production_location VARCHAR(100), -- Country/City
    shipping_method VARCHAR(100),
    
    -- Notes
    supplier_notes TEXT, -- Internal notes for NeoGiga
    customer_visible_notes TEXT, -- Terms, exclusions, clarifications
    
    status VARCHAR(50) DEFAULT 'submitted', -- submitted, revised, withdrawn, awarded, rejected
    
    attachment_ids UUID[], -- Optional supporting docs
    
    submitted_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    revised_at TIMESTAMP WITH TIME ZONE
);

CREATE INDEX idx_rfq_quote_inv ON pcb_rfq_quotes(rfq_invitation_id);
CREATE INDEX idx_rfq_quote_status ON pcb_rfq_quotes(status);
```

### 3.4 Supplier Access Logs
Audit trail for file access.

```sql
CREATE TABLE pcb_supplier_access_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    rfq_invitation_id UUID NOT NULL REFERENCES pcb_rfq_invitations(id),
    supplier_user_id UUID REFERENCES users(id),
    
    action VARCHAR(50), -- 'view_requirements', 'download_gerber', 'download_bom', 'view_cpl'
    ip_address INET,
    user_agent TEXT,
    
    accessed_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_access_log_inv ON pcb_supplier_access_logs(rfq_invitation_id);
```

## 4. RFQ Triggers

### 4.1 Automatic RFQ Creation
System creates RFQ when:
- Instant price engine returns "Engineering Quote Required".
- Board complexity exceeds standard capabilities (e.g., >16 layers, HDI).
- Material requested is non-standard (e.g., Rogers, PTFE).
- Quantity > 1000 units (bulk pricing negotiation).
- Customer explicitly clicks "Request Custom Quote".

### 4.2 Supplier Selection Logic
System suggests suppliers based on:
- **Capability Match:** Supplier supports required layers, material, finish.
- **Region:** Preferred shipping destination.
- **Rating:** Historical performance score.
- **Workload:** Current queue capacity (if integrated).
- **Certification:** ISO, UL, IATF16949 if required.

## 5. Security & Access Control

### 5.1 File Access Tokens
- Suppliers do not receive direct file URLs.
- System generates short-lived, signed tokens (24-72 hours).
- Each download logged in `pcb_supplier_access_logs`.
- Tokens expire automatically; re-request required.

### 5.2 NDA Workflow
- For sensitive projects (confidentiality = high), NDA required.
- Digital acceptance recorded (`nda_accepted_at`, `nda_accepted_by`).
- Files inaccessible until NDA status = accepted.

### 5.3 Quote Isolation
- Supplier A cannot see Supplier B's quote.
- Customer sees all quotes but cannot share externally.
- NeoGiga admins see all for arbitration.

## 6. API Endpoints

### 6.1 Create RFQ
`POST /api/v1/pcb/projects/{uuid}/rfq`

- **Body:** `{ "type": "turnkey", "deadline": "2024-12-01", "target_price": 5000 }`
- **Response:** `{ "rfq_id": "uuid", "status": "draft" }`

### 6.2 Invite Suppliers
`POST /api/v1/pcb/rfq/{rfq_id}/invite`

- **Body:** `{ "supplier_ids": ["uuid1", "uuid2"], "nda_required": true }`
- **Action:** Sends notifications, generates access tokens.

### 6.3 Submit Quote
`POST /api/v1/pcb/rfq/invitations/{inv_id}/quote`

- **Body:** Full pricing breakdown JSON.
- **Permission:** Supplier role + valid invitation.

### 6.4 Award Quote
`POST /api/v1/pcb/rfq/quotes/{quote_id}/award`

- **Action:** Creates cart/order, notifies losing suppliers.
- **Permission:** Project owner or procurement manager.

## 7. Frontend Integration

### 7.1 Supplier Portal Dashboard
- **Inbox:** List of received RFQ invitations.
- **Actions:** Accept/Decline, View Requirements, Download Files.
- **Submission Form:** Structured quote entry with validation.

### 7.2 Customer RFQ Dashboard
- **Status Tracker:** Invited, Responded, No Response.
- **Quote Comparison Table:** Side-by-side view (price, lead time, terms).
- **Award Button:** Convert selected quote to order.

### 7.3 Admin RFQ Center
- **Oversight:** Monitor all active RFQs.
- **Intervention:** Extend deadlines, add suppliers, resolve disputes.
- **Analytics:** Average response time, win rates by supplier.

## 8. Notification Templates

### 8.1 Invitation Email
Subject: "New PCB RFQ Invitation from NeoGiga - [Project Code]"
Body: Summary, deadline, link to portal, NDA requirement notice.

### 8.2 Quote Submitted (Customer)
Subject: "New Quote Received for [Project Code]"
Body: Supplier name, total price, lead time, link to compare.

### 8.3 Quote Awarded (Supplier)
Subject: "RFQ Awarded - [Project Code]"
Body: Congratulations, next steps, PO details.

### 8.4 Quote Not Awarded (Supplier)
Subject: "RFQ Update - [Project Code]"
Body: Thank you, not selected this time, feedback optional.

## 9. Testing Strategy

- **Workflow Tests:** End-to-end RFQ creation → award → order.
- **Security Tests:** Verify supplier cannot access unauthorized files.
- **Concurrency Tests:** Multiple suppliers submitting quotes simultaneously.
- **Expiration Tests:** Token expiry, deadline enforcement.

## 10. Deployment Checklist

- [ ] Seed initial supplier list with contact info.
- [ ] Configure email templates for RFQ notifications.
- [ ] Set up supplier portal routes and permissions.
- [ ] Test NDA workflow with legal team approval.
- [ ] Verify quote isolation (critical security test).
- [ ] Configure cron job to close expired RFQs.

## 11. Future Enhancements

- **Reverse Auction:** Suppliers bid down price in real-time.
- **Capacity Integration:** Real-time factory load data.
- **AI Scoring:** Auto-rank quotes based on risk/value.
- **Multi-Currency:** Suppliers quote in local currency.
