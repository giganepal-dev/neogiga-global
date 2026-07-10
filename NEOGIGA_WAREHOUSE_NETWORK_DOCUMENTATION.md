# NeoGiga Global Warehouse & Distribution Network

## Overview

NeoGiga operates a strategic network of distribution centers across Asia, enabling efficient cross-border e-commerce operations with localized fulfillment capabilities.

---

## 🌍 Warehouse Network Map

```
┌─────────────────────────────────────────────────────────────────┐
│                    NeoGiga Global Network                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  🇨🇳 SHENZHEN (Global DC)          Capacity: 100,000 units     │
│     Qianhai Free Trade Zone                                      │
│     └─ Primary sourcing hub for electronics & manufacturing     │
│                                                                  │
│  🇮🇳 DELHI (Regional DC)             Capacity: 75,000 units      │
│     Manesar Industrial Area, Gurugram                           │
│     └─ South Asia regional hub, India market access             │
│                                                                  │
│  🇳🇵 KATHMANDU (HQ Main DC)          Capacity: 50,000 units      │
│     Birtamod Industrial Corridor, Jhapa                         │
│     └─ Nepal headquarters, primary fulfillment center           │
│                                                                  │
│  🇱🇰 COLOMBO (Regional DC)           Capacity: 40,000 units      │
│     Biyanipura Export Processing Zone                           │
│     └─ Sri Lanka & Indian Ocean region hub                      │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

Total Network Capacity: 265,000 units
All Centers: Cross-border enabled ✅
```

---

## 📦 Warehouse Details

### 1. 🇨🇳 Shenzhen Global Distribution Center (CN-GDC-SZX-001)

**Location:** Qianhai Free Trade Zone, Shenzhen, Guangdong Province, China  
**Coordinates:** 22.5431°N, 114.0579°E  
**Timezone:** Asia/Shanghai (UTC+8)  
**Currency:** CNY (Chinese Yuan)

**Capacity:**
- Total Capacity: 100,000 units
- Current Stock: 0 units (ready for initialization)
- Utilization: 0%

**Operating Hours:**
- Monday - Friday: 08:00 - 20:00
- Saturday: 09:00 - 17:00
- Sunday: Closed

**Contact Information:**
- Phone: +86-755-8888-8888
- Email: shenzhen.warehouse@neogiga.com
- Manager: Li Wei
- Assistant Manager: Zhang Min
- WeChat: NeoGigaShenzhen

**Special Features:**
- ✅ Distribution Center
- ✅ Fulfillment Center
- ✅ Cross-border Shipping Enabled
- Free Trade Zone benefits
- Direct factory sourcing access
- Electronics manufacturing hub proximity

---

### 2. 🇮🇳 Delhi Regional Distribution Center (IN-RDC-DEL-001)

**Location:** Manesar Industrial Area, Gurugram, Haryana, India  
**Coordinates:** 28.3670°N, 76.9072°E  
**Timezone:** Asia/Kolkata (UTC+5:30)  
**Currency:** INR (Indian Rupee)

**Capacity:**
- Total Capacity: 75,000 units
- Current Stock: 0 units (ready for initialization)
- Utilization: 0%

**Operating Hours:**
- Monday - Friday: 08:30 - 19:30
- Saturday: 09:00 - 17:00
- Sunday: Closed

**Contact Information:**
- Phone: +91-124-444-4444
- Email: delhi.warehouse@neogiga.com
- Manager: Priya Patel
- Assistant Manager: Amit Sharma

**Special Features:**
- ✅ Distribution Center
- ✅ Fulfillment Center
- ✅ Cross-border Shipping Enabled
- National Capital Region (NCR) access
- Major highway connectivity
- Airport cargo proximity

---

### 3. 🇳🇵 Kathmandu Main Distribution Center (NP-MDC-KTM-001)

**Location:** Birtamod Industrial Corridor, Jhapa District, Nepal  
**Coordinates:** 26.6667°N, 87.9833°E  
**Timezone:** Asia/Kathmandu (UTC+5:45)  
**Currency:** NPR (Nepalese Rupee)

**Capacity:**
- Total Capacity: 50,000 units
- Current Stock: 0 units (ready for initialization)
- Utilization: 0%

**Operating Hours:**
- Sunday - Thursday: 09:00 - 18:00
- Friday: 09:00 - 15:00
- Saturday: Closed

**Contact Information:**
- Phone: +977-21-555-555
- Email: kathmandu.warehouse@neogiga.com
- Manager: Sunita Sharma
- Assistant Manager: Binod Thapa

**Special Features:**
- ✅ Distribution Center (Nepal HQ)
- ✅ Fulfillment Center
- ✅ Cross-border Shipping Enabled
- Headquarters location
- India border proximity (Kakarbhitta)
- Strategic Himalayan region access

---

### 4. 🇱🇰 Colombo Regional Distribution Center (LK-RDC-CMB-001)

**Location:** Biyanipura Export Processing Zone, Colombo, Sri Lanka  
**Coordinates:** 6.9271°N, 79.8612°E  
**Timezone:** Asia/Colombo (UTC+5:30)  
**Currency:** LKR (Sri Lankan Rupee)

**Capacity:**
- Total Capacity: 40,000 units
- Current Stock: 0 units (ready for initialization)
- Utilization: 0%

**Operating Hours:**
- Monday - Friday: 08:30 - 18:30
- Saturday: 09:00 - 15:00
- Sunday: Closed

**Contact Information:**
- Phone: +94-11-222-2222
- Email: colombo.warehouse@neogiga.com
- Manager: Dilshan Perera
- Assistant Manager: Nishani Fernando

**Special Features:**
- ✅ Distribution Center
- ✅ Fulfillment Center
- ✅ Cross-border Shipping Enabled
- Export Processing Zone benefits
- Port city access (Colombo Harbor)
- Indian Ocean regional hub

---

## 🔧 API Endpoints

### Warehouse Management

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/admin/warehouses` | List all warehouses | ✅ Admin |
| GET | `/api/admin/warehouses/{id}` | Get warehouse details | ✅ Admin |
| POST | `/api/admin/warehouses` | Create new warehouse | ✅ Admin |
| PUT | `/api/admin/warehouses/{id}` | Update warehouse | ✅ Admin |
| DELETE | `/api/admin/warehouses/{id}` | Delete warehouse | ✅ Admin |
| GET | `/api/admin/warehouses/stats` | Get network statistics | ✅ Admin |
| GET | `/api/admin/warehouses/distribution-centers` | Get all DCs | ✅ Admin |
| GET | `/api/admin/warehouses/by-country/{country}` | Get warehouses by country | ✅ Admin |

### Query Parameters for `/api/admin/warehouses`

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `region` | string | Filter by region | `South Asia` |
| `country` | string | Filter by country | `Nepal` |
| `city` | string | Filter by city | `Kathmandu` |
| `status` | string | Filter by status | `active` |
| `distribution_center` | boolean | Filter DCs only | `true` |
| `fulfillment_center` | boolean | Filter FCs only | `true` |
| `per_page` | integer | Results per page | `15` |

---

## 📊 Sample API Responses

### GET `/api/admin/warehouses/stats`

```json
{
  "success": true,
  "data": {
    "total_warehouses": 4,
    "active_warehouses": 4,
    "distribution_centers": 4,
    "fulfillment_centers": 4,
    "cross_border_enabled": 4,
    "east_asia_warehouses": 1,
    "south_asia_warehouses": 3,
    "china_warehouses": 1,
    "india_warehouses": 1,
    "nepal_warehouses": 1,
    "sri_lanka_warehouses": 1,
    "total_capacity_units": 265000,
    "total_current_stock_units": 0,
    "capacity_utilization_percent": 0,
    "total_products": 0,
    "total_reserved": 0,
    "pending_shipments": 0,
    "in_transit_shipments": 0,
    "delivered_shipments": 0
  }
}
```

### GET `/api/admin/warehouses/distribution-centers?region=South+Asia`

```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-delhi",
      "name": "NeoGiga Delhi Regional Distribution Center",
      "code": "IN-RDC-DEL-001",
      "region": "South Asia",
      "country": "India",
      "city": "New Delhi",
      "capacity_units": 75000,
      "is_distribution_center": true,
      "products_count": 0
    },
    {
      "id": "uuid-kathmandu",
      "name": "NeoGiga Kathmandu Main Distribution Center",
      "code": "NP-MDC-KTM-001",
      "region": "South Asia",
      "country": "Nepal",
      "city": "Kathmandu",
      "capacity_units": 50000,
      "is_distribution_center": true,
      "products_count": 0
    },
    {
      "id": "uuid-colombo",
      "name": "NeoGiga Colombo Regional Distribution Center",
      "code": "LK-RDC-CMB-001",
      "region": "South Asia",
      "country": "Sri Lanka",
      "city": "Colombo",
      "capacity_units": 40000,
      "is_distribution_center": true,
      "products_count": 0
    }
  ]
}
```

---

## 🚀 Database Seeding

To seed the warehouse network:

```bash
php artisan db:seed --class=WarehouseSeeder
```

This will create all 4 distribution centers with:
- Unique UUIDs
- Pre-configured contact information
- Operating hours
- Geographic coordinates
- Capacity settings

---

## 🌐 Regional Coverage

### East Asia
- **China (Shenzhen)**: Manufacturing hub, global sourcing
- Coverage: China, Hong Kong, Taiwan, Japan, South Korea

### South Asia
- **India (Delhi)**: Large domestic market, regional hub
- Coverage: India, Pakistan, Bangladesh, Bhutan, Maldives
  
- **Nepal (Kathmandu)**: Headquarters, primary operations
- Coverage: Nepal (nationwide)

- **Sri Lanka (Colombo)**: Island nation hub
- Coverage: Sri Lanka, Maldives

---

## 📈 Strategic Advantages

1. **Cross-Border Efficiency**: All warehouses enabled for international shipping
2. **Free Trade Zones**: Shenzhen (Qianhai), Colombo (EPZ) benefits
3. **Strategic Locations**: Near major ports, airports, and highways
4. **Scalable Capacity**: Total 265,000 units across network
5. **Time Zone Coverage**: UTC+5:30 to UTC+8 for 24/7 operations potential
6. **Multi-Currency Support**: CNY, INR, NPR, LKR
7. **Local Expertise**: Native management teams in each location

---

## 🔒 Security & Compliance

- All warehouses comply with local customs regulations
- Cross-border documentation support
- VAT/tax compliance per jurisdiction
- Secure access controls (role-based)
- Audit logging for all inventory movements

---

## 📞 Emergency Contacts

| Location | Emergency Contact | Backup Contact |
|----------|------------------|----------------|
| Shenzhen | Li Wei (+86-755-8888-8888) | Zhang Min |
| Delhi | Priya Patel (+91-124-444-4444) | Amit Sharma |
| Kathmandu | Sunita Sharma (+977-21-555-555) | Binod Thapa |
| Colombo | Dilshan Perera (+94-11-222-2222) | Nishani Fernando |

---

## 📝 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-07-10 | Initial network setup (4 DCs) |
| | | Removed Dubai, added Shenzhen, Delhi, Colombo |
| | | Updated Kathmandu as HQ Main DC |

---

**Document Last Updated:** July 10, 2025  
**Maintained By:** NeoGiga Operations Team  
**Contact:** operations@neogiga.com
