# AWS SES Setup Guide for NeoGiga

**Document Version:** 1.0  
**Last Updated:** 2026-07-15  
**AWS Region:** ap-south-1 (Asia Pacific - Mumbai)

---

## Prerequisites

Before starting this setup:

1. AWS Account with billing enabled
2. IAM user with administrative access (for initial setup)
3. Domain ownership for neogiga.com and subdomains
4. DNS management access
5. Production use case justification ready

---

## Table of Contents

1. [SES Production Access Request](#1-ses-production-access-request)
2. [Domain Verification](#2-domain-verification)
3. [Easy DKIM Setup](#3-easy-dkim-setup)
4. [Custom MAIL FROM Domain](#4-custom-mail-from-domain)
5. [SPF Record Configuration](#5-spf-record-configuration)
6. [DMARC Policy](#6-dmarc-policy)
7. [Configuration Sets](#7-configuration-sets)
8. [Event Destinations (SNS/SQS)](#8-event-destinations-snssqs)
9. [IAM Policy Creation](#9-iam-policy-creation)
10. [Testing and Validation](#10-testing-and-validation)

---

## 1. SES Production Access Request

### Why Required?

New AWS accounts start in SES sandbox mode with these limitations:
- Can only send to verified email addresses
- Daily sending quota: 200 messages
- Maximum send rate: 1 message/second

### Steps

1. **Navigate to SES Console:**
   ```
   AWS Console → Simple Email Service → Dashboard
   ```

2. **Request Production Access:**
   - Click "Request Production Access" button
   - Fill out the form:

### Use Case Description Template

```
NeoGiga is a global B2B electronics marketplace operating in Nepal, India, UAE, 
and China. We require Amazon SES for:

1. Transactional Emails (Critical):
   - OTP verification for user authentication
   - Password reset emails
   - Order confirmations and invoices
   - Shipping notifications
   - RFQ (Request for Quotation) submissions
   - Quotation notifications

2. Marketing Emails (Consent-based):
   - Newsletter campaigns (opt-in only)
   - Product alerts for watched items
   - Promotional campaigns for registered users
   - Abandoned cart reminders

3. Regional Requirements:
   - Multi-country sender domains (np.neogiga.com, in.neogiga.com, ae.neogiga.com)
   - Compliance with CAN-SPAM, GDPR requirements
   - Unsubscribe mechanisms for all marketing emails

Anti-Spam Measures:
- Double opt-in for all marketing subscriptions
- Immediate suppression list for bounces/complaints
- One-click unsubscribe in all emails
- Physical business address in footer
- Consent tracking with IP and timestamp
- Rate limiting and throttling implementation
- Dedicated IP pool planned for high-volume sending

Expected Volumes (Month 1-3):
- Month 1: 5,000 emails/day (warm-up period)
- Month 2: 15,000 emails/day
- Month 3: 50,000 emails/day (steady state)

Bounce Rate Target: < 2%
Complaint Rate Target: < 0.1%
```

3. **Submit and Wait:**
   - Typical response time: 24-48 hours
   - Monitor SNS topic for approval notification
   - Check email associated with AWS account

---

## 2. Domain Verification

### Domains to Verify

| Domain | Purpose | Type |
|--------|---------|------|
| notify.neogiga.com | Global transactional | Verified |
| campaigns.neogiga.com | Global marketing | Verified |
| notify.np.neogiga.com | Nepal transactional | Verified |
| notify.in.neogiga.com | India transactional | Verified |
| notify.ae.neogiga.com | UAE transactional | Verified |
| notify.cn.neogiga.com | China transactional | Verified |

### Steps for Each Domain

1. **Navigate to SES Console:**
   ```
   AWS Console → Simple Email Service → Verified Identities
   ```

2. **Create Identity:**
   - Click "Create Identity"
   - Select "Domain"
   - Enter domain name (e.g., `notify.neogiga.com`)
   - Click "Create Identity"

3. **Record DNS Records:**
   - Copy all provided DNS records
   - You will need:
     - 3 DKIM CNAME records
     - 1 MX record (for custom MAIL FROM)
     - 1 TXT record (for SPF/MX verification)

---

## 3. Easy DKIM Setup

### Why Easy DKIM?

- Automatic key rotation
- No manual key management
- Better deliverability than standard DKIM
- Required for custom MAIL FROM domains

### Steps

1. **During Domain Verification:**
   - Select "Easy DKIM" option
   - Choose "Generate DKIM records"

2. **Add CNAME Records to DNS:**

   Example records for `notify.neogiga.com`:
   ```
   Type: CNAME
   Name: <unique1>._domainkey.notify.neogiga.com
   Value: <unique1>.dkim.amazonses.com
   
   Type: CNAME
   Name: <unique2>._domainkey.notify.neogiga.com
   Value: <unique2>.dkim.amazonses.com
   
   Type: CNAME
   Name: <unique3>._domainkey.notify.neogiga.com
   Value: <unique3>.dkim.amazonses.com
   ```

3. **Wait for Verification:**
   - DNS propagation: 5 minutes to 48 hours
   - SES console shows status per record
   - All three must show "Verified"

---

## 4. Custom MAIL FROM Domain

### Why Custom MAIL FROM?

- Improves deliverability (separate from From: header)
- Isolates bounce handling
- Professional appearance in email clients
- Required for some ESP integrations

### Setup for Each Domain

1. **In SES Console:**
   - Go to verified domain details
   - Scroll to "MAIL FROM domain" section
   - Click "Edit"

2. **Configure MAIL FROM:**
   - Select "Use a custom MAIL FROM domain"
   - MAIL FROM domain: `bounces.notify.neogiga.com`
   - Behavior on failure: "Use default"

3. **Add DNS Records:**

   **MX Record:**
   ```
   Type: MX
   Name: bounces.notify.neogiga.com
   Value: 10 feedback-smtp.ap-south-1.amazonses.com
   Priority: 10
   ```

   **TXT Record (SPF):**
   ```
   Type: TXT
   Name: bounces.notify.neogiga.com
   Value: "v=spf1 include:amazonses.com ~all"
   ```

4. **Verify Status:**
   - Wait for DNS propagation
   - Status changes to "Verified"

---

## 5. SPF Record Configuration

### Root Domain SPF

If neogiga.com doesn't have an SPF record:

```
Type: TXT
Name: @ (or neogiga.com)
Value: "v=spf1 include:amazonses.com ~all"
```

### If SPF Already Exists

Append `include:amazonses.com`:

**Before:**
```
"v=spf1 include:_spf.google.com ~all"
```

**After:**
```
"v=spf1 include:_spf.google.com include:amazonses.com ~all"
```

⚠️ **Warning:** SPF has a 10-lookup limit. Count all includes.

---

## 6. DMARC Policy

### Recommended DMARC Record

Start with monitoring mode:

```
Type: TXT
Name: _dmarc.neogiga.com
Value: "v=DMARC1; p=none; rua=mailto:dmarc-reports@neogiga.com; ruf=mailto:dmarc-forensics@neogiga.com; fo=1"
```

### Fields Explained

- `p=none` - Monitoring mode (no rejection)
- `rua=` - Aggregate report destination
- `ruf=` - Forensic report destination
- `fo=1` - Generate reports on any alignment failure

### After Warm-up (30 days)

Upgrade to quarantine/reject:

```
"v=DMARC1; p=quarantine; pct=100; rua=mailto:dmarc-reports@neogiga.com"
```

Then eventually:

```
"v=DMARC1; p=reject; pct=100; rua=mailto:dmarc-reports@neogiga.com"
```

---

## 7. Configuration Sets

### Create Configuration Sets

1. **Navigate to SES Console:**
   ```
   AWS Console → Simple Email Service → Configuration Sets
   ```

2. **Create Four Configuration Sets:**

   | Name | Purpose | Event Types |
   |------|---------|-------------|
   | neogiga-transactional | OTP, orders, passwords | Send, Delivery, Bounce, Complaint |
   | neogiga-marketing | Newsletters, promotions | Send, Delivery, Bounce, Complaint, Open, Click |
   | neogiga-rfq | RFQ and quotations | Send, Delivery, Bounce, Complaint |
   | neogiga-seller | Seller notifications | Send, Delivery, Bounce, Complaint |

3. **Steps for Each:**
   - Click "Create configuration set"
   - Enter name
   - Click "Create"

### Add Event Destination

For each configuration set:

1. **Click on configuration set name**
2. **Under "Event destinations", click "Add destination"**
3. **Configure:**
   - Name: `sns-events`
   - Match event types: Select appropriate types
   - Destination: SNS topic
   - SNS topic: Select or create `neogiga-email-events`

---

## 8. Event Destinations (SNS/SQS)

### Architecture

```
SES Configuration Set
    ↓ (events)
SNS Topic: neogiga-email-events
    ↓ (subscription)
SQS Queue: neogiga-email-events-queue
    ↓ (polling)
Laravel Queue Worker
    ↓ (processing)
Database (mail_events table)
```

### Create SNS Topic

1. **Navigate to SNS Console:**
   ```
   AWS Console → Simple Notification Service → Topics
   ```

2. **Create Topic:**
   - Type: Standard
   - Name: `neogiga-email-events`
   - Display name: `NeoGiga Email Events`
   - Click "Create topic"

3. **Note Topic ARN:**
   ```
   arn:aws:sns:ap-south-1:ACCOUNT_ID:neogiga-email-events
   ```

### Create SQS Queue

1. **Navigate to SQS Console:**
   ```
   AWS Console → Simple Queue Service → Queues
   ```

2. **Create Queue:**
   - Type: Standard
   - Name: `neogiga-email-events-queue`
   - Configuration:
     - Message retention: 4 days (345600 seconds)
     - Delivery delay: 0 seconds
     - Visibility timeout: 30 seconds
   - Click "Create queue"

3. **Note Queue ARN:**
   ```
   arn:aws:sqs:ap-south-1:ACCOUNT_ID:neogiga-email-events-queue
   ```

4. **Set Queue Policy:**
   - Go to queue details
   - Access policy tab
   - Add policy to allow SNS:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Principal": {
        "Service": "sns.amazonaws.com"
      },
      "Action": "sqs:SendMessage",
      "Resource": "arn:aws:sqs:ap-south-1:ACCOUNT_ID:neogiga-email-events-queue",
      "Condition": {
        "ArnEquals": {
          "aws:SourceArn": "arn:aws:sns:ap-south-1:ACCOUNT_ID:neogiga-email-events"
        }
      }
    }
  ]
}
```

### Subscribe SQS to SNS

1. **Go to SNS Topic**
2. **Create subscription:**
   - Protocol: SQS
   - Endpoint: Select your SQS queue
   - Click "Create subscription"

3. **Subscription is auto-confirmed** (for SQS)

---

## 9. IAM Policy Creation

### Least-Privilege Policy

Create IAM policy named `NeoGigaSESPolicy`:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "SESSending",
            "Effect": "Allow",
            "Action": [
                "ses:SendEmail",
                "ses:SendRawEmail"
            ],
            "Resource": "*",
            "Condition": {
                "StringLike": {
                    "ses:FromAddress": "*@*.neogiga.com"
                }
            }
        },
        {
            "Sid": "SESQuotaAndStats",
            "Effect": "Allow",
            "Action": [
                "ses:GetSendQuota",
                "ses:GetSendStatistics",
                "sesv2:GetAccount"
            ],
            "Resource": "*"
        },
        {
            "Sid": "SESSuppressionManagement",
            "Effect": "Allow",
            "Action": [
                "sesv2:GetSuppressedDestination",
                "sesv2:ListSuppressedDestinations",
                "sesv2:PutSuppressedDestination",
                "sesv2:DeleteSuppressedDestination"
            ],
            "Resource": "*"
        },
        {
            "Sid": "SNSEvents",
            "Effect": "Allow",
            "Action": [
                "sns:Publish"
            ],
            "Resource": "arn:aws:sns:*:*:neogiga-email-events"
        },
        {
            "Sid": "SQSEvents",
            "Effect": "Allow",
            "Action": [
                "sqs:SendMessage",
                "sqs:ReceiveMessage",
                "sqs:DeleteMessage",
                "sqs:GetQueueAttributes"
            ],
            "Resource": "arn:aws:sqs:*:*:neogiga-email-events-queue"
        }
    ]
}
```

### Create IAM User (if not using roles)

1. **Navigate to IAM Console:**
   ```
   AWS Console → IAM → Users
   ```

2. **Create User:**
   - Username: `neogiga-ses-sender`
   - Select "Attach policies directly"
   - Attach `NeoGigaSESPolicy`
   - Click "Create user"

3. **Create Access Key:**
   - Go to user details
   - Security credentials tab
   - Create access key
   - Select "Application running outside AWS"
   - Download CSV file

### Store Credentials Securely

**Never commit to Git!**

Store in:
- AWS Secrets Manager
- Environment variables
- CI/CD secret store
- `.env` file (excluded from version control)

---

## 10. Testing and Validation

### Pre-Launch Checklist

- [ ] All domains verified
- [ ] DKIM records showing "Verified"
- [ ] MAIL FROM domains verified
- [ ] SPF records propagated
- [ ] DMARC record created
- [ ] Configuration sets created
- [ ] SNS topic created
- [ ] SQS queue created
- [ ] SQS subscribed to SNS
- [ ] IAM policy attached
- [ ] Production access approved

### Test Commands

**Send Test Email (AWS CLI):**
```bash
aws ses send-email \
  --from "test@notify.neogiga.com" \
  --to "your-verified-email@example.com" \
  --subject "SES Test" \
  --message "Body={Data=\"This is a test email\"},Subject={Data=\"SES Test\"}" \
  --region ap-south-1
```

**Check Sending Quota:**
```bash
aws ses get-send-quota --region ap-south-1
```

**Test Configuration Set:**
```bash
aws ses send-email \
  --from "test@notify.neogiga.com" \
  --to "your-verified-email@example.com" \
  --subject "Config Set Test" \
  --message "Body={Data=\"Testing configuration set\"},Subject={Data=\"Config Set Test\"}" \
  --configuration-set-name neogiga-transactional \
  --region ap-south-1
```

### Laravel Test Command

After code deployment:

```bash
php artisan mail:test your-email@example.com --marketplace=np --type=transactional
```

### Monitor Events

1. **Check SQS Queue:**
   ```
   AWS Console → SQS → neogiga-email-events-queue
   ```
   - Messages should appear after sending

2. **Check CloudWatch Metrics:**
   ```
   AWS Console → CloudWatch → Metrics → SES
   ```
   - Sending, Delivery, Bounce, Complaint rates

3. **Check Laravel Database:**
   ```sql
   SELECT * FROM mail_events ORDER BY created_at DESC LIMIT 10;
   ```

---

## Troubleshooting

### Domain Not Verifying

**Symptoms:**
- Domain status stays "Pending verification"
- Individual records show "Not verified"

**Solutions:**
1. Check DNS propagation: `dig <record_name>`
2. Verify no typos in record values
3. Remove trailing dots if present
4. Wait up to 48 hours
5. Contact DNS provider if still failing

### Emails Going to Spam

**Possible Causes:**
- Missing/wrong SPF record
- DKIM not configured
- No DMARC policy
- Poor sender reputation
- Content triggers spam filters

**Solutions:**
1. Verify all DNS records
2. Check sender reputation in SES console
3. Review email content
4. Implement warm-up schedule
5. Monitor bounce/complaint rates

### SNS Events Not Arriving

**Check:**
1. SNS topic ARN matches configuration set
2. SQS subscribed to SNS topic
3. SQS policy allows SNS
4. Event types selected correctly
5. CloudWatch Logs for SNS delivery failures

### IAM Permission Errors

**Error:** `User is not authorized to perform ses:SendEmail`

**Solutions:**
1. Verify IAM policy attached to user/role
2. Check policy Resource conditions
3. Ensure From address matches domain condition
4. Try with AdministratorAccess temporarily to isolate issue

---

## Cost Estimation

### SES Pricing (ap-south-1)

**Sending:**
- First 62,000 emails/month: FREE (when sent from EC2)
- Additional: $0.10 per 1,000 emails

**Example Monthly Costs:**

| Volume | Cost (outside EC2) | Cost (from EC2) |
|--------|-------------------|-----------------|
| 50,000 | $5.00 | FREE |
| 100,000 | $10.00 | FREE |
| 500,000 | $50.00 | FREE |
| 1,000,000 | $100.00 | $38.00 |

**Additional Services:**
- SNS: $0.50 per million requests
- SQS: $0.40 per million requests
- CloudWatch: Free tier + usage

**Estimated Total:** $1-5/month for typical usage

---

## Security Best Practices

1. **Use IAM Roles (not users) when on EC2/ECS**
2. **Rotate access keys every 90 days**
3. **Enable CloudTrail logging for SES**
4. **Monitor unusual sending patterns**
5. **Set up billing alarms**
6. **Use VPC endpoints for private connectivity**
7. **Encrypt SQS messages at rest**
8. **Restrict SES to specific regions**

---

## Next Steps

After completing AWS setup:

1. Install AWS SDK in Laravel project
2. Configure environment variables
3. Run database migrations
4. Deploy application code
5. Start queue workers
6. Send test emails
7. Monitor event processing
8. Begin warm-up schedule

---

## Support Resources

- [AWS SES Documentation](https://docs.aws.amazon.com/ses/)
- [SES Developer Guide](https://docs.aws.amazon.com/ses/latest/dg/)
- [AWS SES Forum](https://repost.aws/tags/TASESAmazonSimpleEmailService)
- [Laravel Mail Documentation](https://laravel.com/docs/mail)
- [NeoGiga Internal Docs](./AMAZON_SES_EMAIL_AUDIT.md)

---

**Document Maintained By:** NeoGiga DevOps Team  
**Last Reviewed:** 2026-07-15  
**Next Review:** Quarterly or after major AWS updates
