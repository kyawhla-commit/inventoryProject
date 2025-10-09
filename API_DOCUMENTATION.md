# Inventory Management API Documentation

## Base URL

```
http://your-domain.com/api
```

## Authentication

All protected endpoints require a Bearer token in the Authorization header:

```
Authorization: Bearer your_token_here
```

## Endpoints

### Authentication

#### Register User

```http
POST /auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

#### Login

```http
POST /auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```

#### Logout

```http
POST /auth/logout
Authorization: Bearer token
```

#### Get Profile

```http
GET /auth/profile
Authorization: Bearer token
```

### Products

#### Get All Products

```http
GET /products?search=laptop&category_id=1&status=active&per_page=20
Authorization: Bearer token
```

#### Create Product

```http
POST /products
Authorization: Bearer token
Content-Type: application/json

{
    "name": "Laptop",
    "sku": "LAP001",
    "category_id": 1,
    "price": 999.99,
    "cost": 750.00,
    "quantity": 50,
    "min_quantity": 10,
    "description": "High-performance laptop",
    "status": "active"
}
```

#### Get Single Product

```http
GET /products/{id}
Authorization: Bearer token
```

#### Update Product

```http
PUT /products/{id}
Authorization: Bearer token
Content-Type: application/json

{
    "name": "Updated Laptop",
    "price": 1099.99
}
```

#### Delete Product

```http
DELETE /products/{id}
Authorization: Bearer token
```

#### Get Low Stock Products

```http
GET /products/low-stock
Authorization: Bearer token
```

### Categories

#### Get All Categories

```http
GET /categories?search=electronics
Authorization: Bearer token
```

#### Create Category

```http
POST /categories
Authorization: Bearer token
Content-Type: application/json

{
    "name": "Electronics",
    "description": "Electronic devices and accessories"
}
```

#### Get Single Category

```http
GET /categories/{id}
Authorization: Bearer token
```

#### Update Category

```http
PUT /categories/{id}
Authorization: Bearer token
Content-Type: application/json

{
    "name": "Updated Electronics"
}
```

#### Delete Category

```http
DELETE /categories/{id}
Authorization: Bearer token
```

### Raw Materials

#### Get All Raw Materials

```http
GET /raw-materials?search=steel&unit=kg&per_page=20
Authorization: Bearer token
```

#### Create Raw Material

```http
POST /raw-materials
Authorization: Bearer token
Content-Type: application/json

{
    "name": "Steel Rod",
    "unit": "kg",
    "cost_per_unit": 5.50,
    "current_stock": 1000,
    "min_stock_level": 100,
    "description": "High-grade steel rod"
}
```

#### Get Single Raw Material

```http
GET /raw-materials/{id}
Authorization: Bearer token
```

#### Update Raw Material

```http
PUT /raw-materials/{id}
Authorization: Bearer token
Content-Type: application/json

{
    "cost_per_unit": 6.00,
    "current_stock": 1200
}
```

#### Delete Raw Material

```http
DELETE /raw-materials/{id}
Authorization: Bearer token
```

#### Get Low Stock Raw Materials

```http
GET /raw-materials/low-stock
Authorization: Bearer token
```

#### Update Stock

```http
PATCH /raw-materials/{id}/stock
Authorization: Bearer token
Content-Type: application/json

{
    "quantity": 100,
    "type": "add",
    "reason": "New purchase received"
}
```

Types: `add`, `subtract`, `set`

### Suppliers

#### Get All Suppliers

```http
GET /suppliers?search=acme&per_page=20
Authorization: Bearer token
```

#### Create Supplier

```http
POST /suppliers
Authorization: Bearer token
Content-Type: application/json

{
    "name": "ACME Corp",
    "email": "contact@acme.com",
    "phone": "+1234567890",
    "address": "123 Business St, City, State",
    "contact_person": "John Smith",
    "notes": "Reliable supplier for electronics"
}
```

#### Get Single Supplier

```http
GET /suppliers/{id}
Authorization: Bearer token
```

#### Update Supplier

```http
PUT /suppliers/{id}
Authorization: Bearer token
Content-Type: application/json

{
    "phone": "+1234567891",
    "contact_person": "Jane Smith"
}
```

#### Delete Supplier

```http
DELETE /suppliers/{id}
Authorization: Bearer token
```

### Dashboard

#### Get Overview

```http
GET /dashboard/overview
Authorization: Bearer token
```

#### Get Sales Statistics

```http
GET /dashboard/sales-stats?period=month
Authorization: Bearer token
```

Periods: `day`, `week`, `month`, `year`

#### Get Inventory Value

```http
GET /dashboard/inventory-value
Authorization: Bearer token
```

## Response Format

### Success Response

```json
{
    "success": true,
    "message": "Operation successful",
    "data": {
        // Response data here
    }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        "field": ["Validation error message"]
    }
}
```

### Pagination Response

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [...],
        "first_page_url": "...",
        "from": 1,
        "last_page": 5,
        "last_page_url": "...",
        "next_page_url": "...",
        "path": "...",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 67
    }
}
```

## Testing the API

You can test these endpoints using tools like:

-   Postman
-   Insomnia
-   cURL
-   Your frontend application

### Example cURL Request

````bash
curl -X POST http://your-domain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'
```###
Recipes

#### Get All Recipes
```http
GET /recipes?search=cake&product_id=1&is_active=true&per_page=20
Authorization: Bearer token
````

#### Create Recipe

```http
POST /recipes
Authorization: Bearer token
Content-Type: application/json

{
    "product_id": 1,
    "name": "Chocolate Cake Recipe",
    "description": "Delicious chocolate cake recipe",
    "batch_size": 10,
    "unit": "pieces",
    "yield_percentage": 95,
    "preparation_time": 30,
    "production_time": 60,
    "instructions": "1. Mix ingredients\n2. Bake at 350°F\n3. Cool and serve",
    "is_active": true,
    "version": "1.0",
    "items": [
        {
            "raw_material_id": 1,
            "quantity_required": 2.5,
            "unit": "kg",
            "cost_per_unit": 5.00,
            "waste_percentage": 5,
            "notes": "High quality flour",
            "sequence_order": 1
        },
        {
            "raw_material_id": 2,
            "quantity_required": 1.0,
            "unit": "kg",
            "cost_per_unit": 8.00,
            "waste_percentage": 2,
            "notes": "Premium chocolate",
            "sequence_order": 2
        }
    ]
}
```

#### Get Single Recipe

```http
GET /recipes/{id}
Authorization: Bearer token
```

#### Update Recipe

```http
PUT /recipes/{id}
Authorization: Bearer token
Content-Type: application/json

{
    "name": "Updated Chocolate Cake Recipe",
    "batch_size": 12,
    "is_active": false,
    "items": [
        {
            "raw_material_id": 1,
            "quantity_required": 3.0,
            "unit": "kg",
            "cost_per_unit": 5.50,
            "waste_percentage": 3,
            "notes": "Updated flour quantity",
            "sequence_order": 1
        }
    ]
}
```

#### Delete Recipe

```http
DELETE /recipes/{id}
Authorization: Bearer token
```

#### Calculate Material Requirements

```http
POST /recipes/{id}/calculate-materials
Authorization: Bearer token
Content-Type: application/json

{
    "quantity": 50
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "recipe": {...},
        "requested_quantity": 50,
        "batch_multiplier": 5,
        "material_requirements": [
            {
                "raw_material_id": 1,
                "raw_material": {...},
                "required_quantity": 12.5,
                "waste_quantity": 0.625,
                "total_required": 13.125,
                "unit": "kg",
                "estimated_cost": 65.625
            }
        ],
        "total_estimated_cost": 125.50
    }
}
```

#### Duplicate Recipe

```http
POST /recipes/{id}/duplicate
Authorization: Bearer token
```

## Recipe Management Features

### Recipe Structure

-   **Product Association**: Each recipe is linked to a specific product
-   **Batch-based**: Recipes define quantities for a specific batch size
-   **Material Requirements**: Each recipe contains multiple raw material items
-   **Cost Calculation**: Automatic calculation of material costs and per-unit costs
-   **Waste Management**: Accounts for waste percentage in material calculations

### Recipe Items

-   **Raw Material**: Each item references a raw material
-   **Quantity Required**: Base quantity needed for the batch size
-   **Waste Percentage**: Expected waste during production
-   **Sequence Order**: Order of operations in the recipe
-   **Cost Override**: Optional cost override per unit

### Material Calculations

-   **Batch Multiplier**: Automatically calculates how many batches needed
-   **Waste Inclusion**: Adds waste percentage to required quantities
-   **Cost Estimation**: Calculates total estimated cost for production
-   **Stock Requirements**: Shows exactly what materials are needed

### Recipe Versioning

-   **Version Control**: Track different versions of recipes
-   **Active Status**: Enable/disable recipes without deletion
-   **Duplication**: Create copies of existing recipes for modification

### Production Integration

-   Recipes integrate with production planning
-   Cannot delete recipes that are used in active production plans
-   Material requirements feed into purchase planning## Prod
    uction Planning API 🏭

### Production Plans

#### Get All Production Plans

```http
GET /production-plans?search=plan&status=approved&date_from=2024-01-01&date_to=2024-12-31&per_page=20
Authorization: Bearer token
```

#### Create Production Plan

```http
POST /production-plans
Authorization: Bearer token
Content-Type: application/json

{
    "name": "Weekly Production Plan - Week 1",
    "description": "Production plan for first week of January",
    "planned_start_date": "2024-01-15",
    "planned_end_date": "2024-01-21",
    "status": "draft",
    "notes": "Priority on chocolate products",
    "items": [
        {
            "product_id": 1,
            "recipe_id": 1,
            "order_id": 5,
            "planned_quantity": 100,
            "unit": "pieces",
            "planned_start_date": "2024-01-15",
            "planned_end_date": "2024-01-17",
            "priority": 1,
            "notes": "High priority order"
        },
        {
            "product_id": 2,
            "recipe_id": 2,
            "planned_quantity": 50,
            "unit": "pieces",
            "planned_start_date": "2024-01-18",
            "planned_end_date": "2024-01-21",
            "priority": 2,
            "notes": "Regular production"
        }
    ]
}
```

#### Get Single Production Plan

```http
GET /production-plans/{id}
Authorization: Bearer token
```

#### Update Production Plan

```http
PUT /production-plans/{id}
Authorization: Bearer token
Content-Type: application/json

{
    "name": "Updated Production Plan",
    "status": "approved",
    "items": [
        {
            "product_id": 1,
            "recipe_id": 1,
            "planned_quantity": 120,
            "unit": "pieces",
            "planned_start_date": "2024-01-15",
            "planned_end_date": "2024-01-17",
            "priority": 1
        }
    ]
}
```

#### Delete Production Plan

```http
DELETE /production-plans/{id}
Authorization: Bearer token
```

#### Update Production Plan Status

```http
PATCH /production-plans/{id}/status
Authorization: Bearer token
Content-Type: application/json

{
    "status": "approved"
}
```

Status options: `draft`, `approved`, `in_progress`, `completed`, `cancelled`

#### Get Material Requirements for Production Plan

```http
GET /production-plans/{id}/material-requirements
Authorization: Bearer token
```

**Response:**

```json
{
    "success": true,
    "data": {
        "production_plan": {...},
        "material_requirements": [
            {
                "raw_material_id": 1,
                "raw_material": {...},
                "total_required": 25.5,
                "estimated_cost": 127.50,
                "current_stock": 30.0,
                "shortage": 0,
                "sufficient_stock": true
            }
        ],
        "total_estimated_cost": 500.00,
        "materials_with_shortage": 0
    }
}
```

#### Get Production Plans by Status

```http
GET /production-plans/status/{status}
Authorization: Bearer token
```

### Production Plan Items

#### Get Single Production Plan Item

```http
GET /production-plan-items/{id}
Authorization: Bearer token
```

#### Update Production Plan Item Status

```http
PATCH /production-plan-items/{id}/status
Authorization: Bearer token
Content-Type: application/json

{
    "status": "in_progress"
}
```

Status options: `pending`, `in_progress`, `completed`, `cancelled`

#### Update Production Progress

```http
PATCH /production-plan-items/{id}/progress
Authorization: Bearer token
Content-Type: application/json

{
    "actual_quantity": 95,
    "actual_material_cost": 245.50,
    "actual_start_date": "2024-01-15",
    "actual_end_date": "2024-01-17",
    "notes": "Production completed successfully",
    "consume_materials": true
}
```

#### Get Material Requirements for Production Item

```http
GET /production-plan-items/{id}/material-requirements
Authorization: Bearer token
```

#### Get Production Items by Priority

```http
GET /production-plan-items/by-priority?status=pending
Authorization: Bearer token
```

#### Get Overdue Production Items

```http
GET /production-plan-items/overdue
Authorization: Bearer token
```

## Production Planning Features

### Production Plan Management

-   **Plan Creation**: Create comprehensive production plans with multiple items
-   **Status Tracking**: Track plans through draft → approved → in_progress → completed
-   **Cost Estimation**: Automatic calculation of material costs based on recipes
-   **Date Planning**: Plan start and end dates for entire plans and individual items

### Material Requirements Planning (MRP)

-   **Automatic Calculation**: Calculate material requirements based on recipes and quantities
-   **Stock Validation**: Check if sufficient raw materials are available
-   **Shortage Identification**: Identify materials that need to be purchased
-   **Cost Estimation**: Calculate total estimated costs for production

### Production Scheduling

-   **Priority Management**: Set priorities for production items (1-10 scale)
-   **Date Scheduling**: Plan start and end dates for each production item
-   **Order Integration**: Link production items to customer orders
-   **Overdue Tracking**: Identify overdue production items

### Progress Tracking

-   **Real-time Updates**: Update actual quantities, costs, and dates
-   **Variance Analysis**: Calculate variance between planned and actual results
-   **Efficiency Metrics**: Track production efficiency percentages
-   **Material Consumption**: Automatically consume raw materials when production completes

### Production Workflow

1. **Create Plan**: Define what to produce and when
2. **Check Materials**: Verify material availability
3. **Approve Plan**: Approve for production start
4. **Start Production**: Begin manufacturing process
5. **Track Progress**: Update actual quantities and costs
6. **Complete Production**: Finish items and update inventory

### Integration Points

-   **Orders**: Link production to customer orders
-   **Recipes**: Use recipes to calculate material requirements
-   **Inventory**: Consume raw materials and add finished products
-   **Costs**: Track actual vs estimated costs for analysis

## Inventory Transactions API 📊

### Inventory Transactions

#### Get All Inventory Transactions

```http
GET /inventory-transactions?search=TXN&type=in&source=purchase&product_id=1&date_from=2024-01-01&date_to=2024-12-31&per_page=20
Authorization: Bearer token
```

#### Create Manual Inventory Transaction

```http
POST /inventory-transactions
Authorization: Bearer token
Content-Type: application/json

{
    "type": "in",
    "source": "adjustment",
    "product_id": 1,
    "quantity": 50,
    "unit_cost": 10.50,
    "unit": "pcs",
    "reason": "Stock correction",
    "notes": "Found additional inventory during audit",
    "transaction_date": "2024-01-15T10:30:00Z"
}
```

**Transaction Types:**

-   `in` - Stock increase
-   `out` - Stock decrease
-   `adjustment` - Stock adjustment

**Transaction Sources:**

-   `sale` - From sales transactions
-   `purchase` - From purchase receipts
-   `production` - From production activities
-   `adjustment` - Manual adjustments
-   `return` - Customer/supplier returns
-   `transfer` - Stock transfers
-   `waste` - Waste/damage write-offs

#### Get Single Transaction

```http
GET /inventory-transactions/{id}
Authorization: Bearer token
```

#### Get Item History

```http
GET /inventory-transactions/item/history?product_id=1&date_from=2024-01-01&date_to=2024-12-31
Authorization: Bearer token
```

**Response:**

```json
{
    "success": true,
    "data": {
        "item": {...},
        "current_stock": 150,
        "transactions": [...],
        "summary": {
            "total_transactions": 25,
            "total_in": 200,
            "total_out": 75,
            "total_adjustments": 5
        }
    }
}
```

#### Create Stock Adjustment

```http
POST /inventory-transactions/adjustments
Authorization: Bearer token
Content-Type: application/json

{
    "product_id": 1,
    "adjustment_type": "increase",
    "quantity": 25,
    "reason": "Physical count correction",
    "notes": "Found additional stock during monthly audit",
    "unit_cost": 12.00
}
```

**Adjustment Types:**

-   `increase` - Add to current stock
-   `decrease` - Subtract from current stock
-   `set` - Set stock to specific amount

#### Get Stock Movements Report

```http
GET /inventory-transactions/reports/movements?date_from=2024-01-01&date_to=2024-01-31
Authorization: Bearer token
```

**Response:**

```json
{
    "success": true,
    "data": {
        "date_range": {
            "from": "2024-01-01",
            "to": "2024-01-31"
        },
        "total_transactions": 150,
        "totals_by_type": {
            "in": 500,
            "out": 350,
            "adjustments": 25
        },
        "totals_by_source": {
            "sale": {
                "count": 45,
                "total_quantity": -300,
                "total_cost": 15000
            },
            "purchase": {
                "count": 12,
                "total_quantity": 400,
                "total_cost": 8000
            },
            "production": {
                "count": 8,
                "total_quantity": 100,
                "total_cost": 2500
            }
        }
    }
}
```

#### Get Low Stock Items Report

```http
GET /inventory-transactions/reports/low-stock
Authorization: Bearer token
```

**Response:**

```json
{
    "success": true,
    "data": {
        "low_stock_items": [
            {
                "type": "product",
                "id": 1,
                "name": "Laptop",
                "current_stock": 5,
                "min_stock": 10,
                "unit": "pcs",
                "category": "Electronics"
            }
        ],
        "summary": {
            "total_items": 8,
            "products": 5,
            "raw_materials": 3
        }
    }
}
```

## Inventory Transaction Features

### Automatic Transaction Recording

-   **Sales Integration**: Automatically records outbound transactions when sales are made
-   **Purchase Integration**: Automatically records inbound transactions when purchases are received
-   **Production Integration**: Records raw material consumption and finished product output
-   **Real-time Updates**: Stock levels updated immediately with each transaction

### Manual Transaction Management

-   **Direct Transactions**: Create manual inventory transactions for special cases
-   **Stock Adjustments**: Easy adjustment interface for corrections
-   **Multiple Adjustment Types**: Increase, decrease, or set stock to specific amounts
-   **Reason Tracking**: Mandatory reasons for all adjustments

### Audit Trail & History

-   **Complete History**: Full transaction history for every item
-   **Running Balances**: Track stock levels over time
-   **Source Tracking**: Know exactly where each transaction originated
-   **User Tracking**: Track who made each transaction
-   **Approval Workflow**: Optional approval process for adjustments

### Reporting & Analytics

-   **Stock Movements**: Comprehensive movement reports by date range
-   **Low Stock Alerts**: Identify items below minimum levels
-   **Transaction Analysis**: Analyze transactions by type, source, and date
-   **Variance Reports**: Track differences between expected and actual stock

### Stock Level Management

-   **Real-time Balances**: Always accurate stock levels
-   **Historical Balances**: View stock levels at any point in time
-   **Negative Stock Prevention**: Prevents transactions that would create negative stock
-   **Multi-unit Support**: Handle different units of measurement

### Integration Points

-   **Sales System**: Automatic outbound transactions
-   **Purchase System**: Automatic inbound transactions
-   **Production System**: Material consumption and output tracking
-   **Adjustment System**: Manual corrections and adjustments
-   **Reporting System**: Data source for all inventory reports

### Transaction Workflow

1. **Automatic Recording**: System automatically creates transactions for sales, purchases, production
2. **Manual Adjustments**: Users can create manual adjustments with reasons
3. **Stock Updates**: Stock levels updated immediately
4. **Audit Trail**: Complete history maintained for compliance
5. **Reporting**: Generate reports for analysis and decision making

## Reports & Analytics API 📈

### Business Reports

#### Sales Report

```http
GET /reports/sales?period=monthly&group_by=day
Authorization: Bearer token
```

**Parameters:**

-   `period`: `daily`, `weekly`, `monthly`, `yearly`, `custom`
-   `date_from`: Required if period is `custom`
-   `date_to`: Required if period is `custom`
-   `group_by`: `day`, `week`, `month`, `year`, `product`, `customer`

**Response:**

```json
{
    "success": true,
    "data": {
        "period": "monthly",
        "date_range": {
            "start": "2024-01-01T00:00:00Z",
            "end": "2024-01-31T23:59:59Z"
        },
        "summary": {
            "total_sales": 25000.00,
            "total_orders": 150,
            "average_order_value": 166.67
        },
        "grouped_data": {...},
        "top_products": [...],
        "top_customers": [...]
    }
}
```

#### Inventory Valuation Report

```http
GET /reports/inventory-valuation?valuation_method=cost&include_raw_materials=true
Authorization: Bearer token
```

**Parameters:**

-   `valuation_method`: `cost`, `selling_price`, `average`
-   `include_raw_materials`: `true`/`false`

#### Profit & Loss Report

```http
GET /reports/profit-loss?period=monthly
Authorization: Bearer token
```

**Response:**

```json
{
    "success": true,
    "data": {
        "period": "monthly",
        "revenue": {
            "total_sales": 50000.0,
            "number_of_orders": 200
        },
        "costs": {
            "cost_of_goods_sold": 30000.0,
            "purchase_costs": 15000.0,
            "production_costs": 5000.0,
            "total_operating_expenses": 20000.0
        },
        "profit": {
            "gross_profit": 20000.0,
            "gross_profit_margin": 40.0,
            "net_profit": 0.0,
            "net_profit_margin": 0.0
        }
    }
}
```

#### Production Efficiency Report

```http
GET /reports/production-efficiency?period=monthly
Authorization: Bearer token
```

#### Low Stock Alert Report

```http
GET /reports/low-stock-alert?threshold_multiplier=1.5
Authorization: Bearer token
```

### Advanced Analytics

#### Sales Trends Analysis

```http
GET /analytics/sales-trends?period=30days&metric=revenue
Authorization: Bearer token
```

**Parameters:**

-   `period`: `7days`, `30days`, `90days`, `1year`
-   `metric`: `revenue`, `orders`, `average_order_value`

**Response:**

```json
{
    "success": true,
    "data": {
        "period": "30days",
        "metric": "revenue",
        "trend_percentage": 15.5,
        "trend_direction": "up",
        "daily_data": [...],
        "summary": {
            "total_revenue": 75000.00,
            "total_orders": 450,
            "average_daily_revenue": 2500.00,
            "average_daily_orders": 15
        }
    }
}
```

#### Product Performance Analysis

```http
GET /analytics/product-performance?period=90days&sort_by=revenue&limit=20
Authorization: Bearer token
```

**Parameters:**

-   `period`: `30days`, `90days`, `1year`
-   `sort_by`: `revenue`, `quantity`, `profit_margin`
-   `limit`: Number of products to return (5-50)

**Response:**

```json
{
    "success": true,
    "data": {
        "period": "90days",
        "sort_by": "revenue",
        "products": [
            {
                "product_id": 1,
                "product_name": "Laptop",
                "category": "Electronics",
                "total_quantity": 50,
                "total_revenue": 50000.00,
                "total_cost": 37500.00,
                "profit": 12500.00,
                "profit_margin": 25.00,
                "order_frequency": 25,
                "average_selling_price": 1000.00
            }
        ],
        "summary": {...}
    }
}
```

#### Customer Analytics

```http
GET /analytics/customer-analytics?period=90days
Authorization: Bearer token
```

**Response:**

```json
{
    "success": true,
    "data": {
        "period": "90days",
        "customers": [...],
        "segments": {
            "high_value": 15,
            "medium_value": 45,
            "low_value": 120,
            "frequent_buyers": 25,
            "recent_customers": 80,
            "at_risk_customers": 30
        },
        "summary": {
            "total_customers": 180,
            "active_customers": 150,
            "average_customer_value": 500.00,
            "average_order_frequency": 3.2
        }
    }
}
```

#### Inventory Turnover Analysis

```http
GET /analytics/inventory-turnover?period=monthly&category_id=1
Authorization: Bearer token
```

**Parameters:**

-   `period`: `monthly`, `quarterly`, `yearly`
-   `category_id`: Optional category filter

**Response:**

```json
{
    "success": true,
    "data": {
        "period": "monthly",
        "products": [
            {
                "product_id": 1,
                "product_name": "Laptop",
                "category": "Electronics",
                "current_stock": 25,
                "total_sold": 50,
                "turnover_ratio": 2.0,
                "days_in_inventory": 15.0,
                "turnover_category": "fast"
            }
        ],
        "summary": {
            "average_turnover_ratio": 1.5,
            "fast_moving_items": 15,
            "slow_moving_items": 8,
            "dead_stock_items": 2
        }
    }
}
```

#### Demand Forecasting

```http
GET /analytics/forecast-demand?product_id=1&forecast_days=30
Authorization: Bearer token
```

**Parameters:**

-   `product_id`: Required product ID
-   `forecast_days`: Days to forecast (7-365, default: 30)

**Response:**

```json
{
    "success": true,
    "data": {
        "product_id": 1,
        "product_name": "Laptop",
        "forecast_period": 30,
        "current_stock": 25,
        "historical_analysis": {
            "days_analyzed": 90,
            "average_daily_demand": 1.67,
            "trend": 0.0123,
            "total_historical_demand": 150
        },
        "forecast": [
            {
                "date": "2024-01-16",
                "forecasted_demand": 1.68
            }
        ],
        "forecast_summary": {
            "total_forecasted_demand": 52.5,
            "average_daily_forecast": 1.75,
            "estimated_stockout_date": "2024-02-01",
            "recommended_reorder_quantity": 27.5
        }
    }
}
```

## Reports & Analytics Features

### Business Intelligence Reports

-   **Sales Reports**: Comprehensive sales analysis with multiple grouping options
-   **Inventory Valuation**: Real-time inventory value using different methods
-   **Profit & Loss**: Complete P&L statements with cost breakdowns
-   **Production Efficiency**: Manufacturing performance metrics
-   **Low Stock Alerts**: Proactive inventory management

### Advanced Analytics

-   **Trend Analysis**: Identify sales trends and patterns
-   **Product Performance**: Analyze product profitability and popularity
-   **Customer Segmentation**: Understand customer behavior and value
-   **Inventory Turnover**: Optimize inventory levels and identify slow movers
-   **Demand Forecasting**: Predict future demand using historical data

### Key Metrics Tracked

-   **Sales Metrics**: Revenue, orders, average order value, growth rates
-   **Inventory Metrics**: Stock levels, turnover ratios, days in inventory
-   **Customer Metrics**: Lifetime value, frequency, recency, segments
-   **Production Metrics**: Efficiency, cost variance, on-time delivery
-   **Profitability Metrics**: Gross profit, net profit, margins by product

### Reporting Capabilities

-   **Flexible Periods**: Daily, weekly, monthly, quarterly, yearly, custom ranges
-   **Multiple Formats**: JSON data ready for charts, tables, exports
-   **Real-time Data**: Always up-to-date information
-   **Drill-down Analysis**: From summary to detailed item-level data
-   **Comparative Analysis**: Period-over-period comparisons

### Forecasting & Predictions

-   **Demand Forecasting**: Predict future product demand
-   **Stock-out Predictions**: Estimate when items will run out
-   **Reorder Recommendations**: Suggest optimal reorder quantities
-   **Trend Projections**: Project sales and inventory trends

### Business Insights

-   **Top Performers**: Best-selling products and customers
-   **Underperformers**: Slow-moving inventory and at-risk customers
-   **Seasonal Patterns**: Identify seasonal trends and cycles
-   **Efficiency Opportunities**: Areas for operational improvement
## Advanced Features API ⚡

### Barcode/QR Code Management

#### Generate Barcode
```http
POST /barcodes/generate
Authorization: Bearer token
Content-Type: application/json

{
    "type": "product",
    "item_id": 1,
    "format": "code128",
    "size": "medium"
}
```

**Parameters:**
- `type`: `product`, `raw_material`
- `format`: `code128`, `ean13`, `qr`
- `size`: `small`, `medium`, `large`

**Response:**
```json
{
    "success": true,
    "data": {
        "item_type": "product",
        "item_id": 1,
        "item_name": "Laptop",
        "code": "PRD-000001",
        "format": "code128",
        "barcode_data": {...},
        "svg_url": "/api/barcodes/svg?code=PRD-000001&format=code128",
        "png_url": "/api/barcodes/png?code=PRD-000001&format=code128"
    }
}
```

#### Generate Bulk Barcodes
```http
POST /barcodes/generate-bulk
Authorization: Bearer token
Content-Type: application/json

{
    "items": [
        {"type": "product", "item_id": 1},
        {"type": "product", "item_id": 2},
        {"type": "raw_material", "item_id": 1}
    ],
    "format": "code128",
    "size": "medium"
}
```

#### Scan Barcode
```http
POST /barcodes/scan
Authorization: Bearer token
Content-Type: application/json

{
    "code": "PRD-000001"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "found": true,
        "type": "product",
        "item": {...},
        "stock_info": {
            "current_stock": 25,
            "min_stock": 10,
            "is_low_stock": false
        }
    }
}
```

#### Get Barcode Images
```http
GET /barcodes/svg?code=PRD-000001&format=code128&size=medium
GET /barcodes/png?code=PRD-000001&format=code128&size=medium
```

### Notifications System

#### Get Notifications
```http
GET /notifications?status=unread&type=low_stock&priority=high&per_page=20
Authorization: Bearer token
```

#### Create Notification
```http
POST /notifications
Authorization: Bearer token
Content-Type: application/json

{
    "type": "low_stock",
    "title": "Low Stock Alert",
    "message": "Product 'Laptop' is running low on stock",
    "priority": "high",
    "product_id": 1,
    "data": {
        "current_stock": 5,
        "min_stock": 10
    }
}
```

#### Mark Notification as Read
```http
PATCH /notifications/{id}/read
Authorization: Bearer token
```

#### Mark All Notifications as Read
```http
PATCH /notifications/mark-all-read
Authorization: Bearer token
```

#### Get Unread Count
```http
GET /notifications/unread/count
Authorization: Bearer token
```

#### Check Low Stock (Auto-generate notifications)
```http
POST /notifications/check-low-stock
Authorization: Bearer token
```

#### Create Order Notification
```http
POST /notifications/order-update
Authorization: Bearer token
Content-Type: application/json

{
    "order_id": 1,
    "status": "shipped",
    "message": "Your order has been shipped"
}
```

#### Get Notification Stats
```http
GET /notifications/stats
Authorization: Bearer token
```

### File Management

#### Upload Product Image
```http
POST /files/upload/product-image
Authorization: Bearer token
Content-Type: multipart/form-data

{
    "product_id": 1,
    "image": [file],
    "is_primary": true
}
```

#### Upload Document
```http
POST /files/upload/document
Authorization: Bearer token
Content-Type: multipart/form-data

{
    "type": "product",
    "item_id": 1,
    "document": [file],
    "document_type": "specification",
    "description": "Product specification sheet"
}
```

**Document Types:**
- `specification`
- `manual`
- `certificate`
- `invoice`
- `other`

#### Upload Bulk Images
```http
POST /files/upload/bulk-images
Authorization: Bearer token
Content-Type: multipart/form-data

{
    "images": [file1, file2, file3],
    "product_ids": [1, 2, 3]
}
```

#### Delete File
```http
DELETE /files/delete
Authorization: Bearer token
Content-Type: application/json

{
    "path": "products/images/product_1_image.jpg",
    "type": "product_image",
    "item_id": 1
}
```

#### Get Files
```http
GET /files/list?type=product&item_id=1
Authorization: Bearer token
```

#### Get Storage Info
```http
GET /files/storage-info
Authorization: Bearer token
```

### Data Export & Backup

#### Export Products
```http
POST /export/products
Authorization: Bearer token
Content-Type: application/json

{
    "format": "csv",
    "category_id": 1,
    "include_images": true
}
```

**Export Formats:**
- `csv`
- `json`
- `excel`

#### Export Inventory Transactions
```http
POST /export/inventory-transactions
Authorization: Bearer token
Content-Type: application/json

{
    "format": "csv",
    "date_from": "2024-01-01",
    "date_to": "2024-01-31",
    "type": "out",
    "source": "sale"
}
```

#### Export Sales Report
```http
POST /export/sales-report
Authorization: Bearer token
Content-Type: application/json

{
    "format": "csv",
    "date_from": "2024-01-01",
    "date_to": "2024-01-31",
    "include_items": true
}
```

#### Create Full Backup
```http
POST /backup/create
Authorization: Bearer token
Content-Type: application/json

{
    "tables": ["products", "sales", "inventory_transactions"],
    "include_files": true
}
```

#### Get Export History
```http
GET /export/history
Authorization: Bearer token
```

#### Delete Export File
```http
DELETE /export/delete
Authorization: Bearer token
Content-Type: application/json

{
    "filename": "products_export_2024-01-15_10-30-00.csv"
}
```

## Advanced Features Overview

### Barcode/QR Code System
- **Generate Barcodes**: Create barcodes for products and raw materials
- **Multiple Formats**: Support for Code128, EAN13, and QR codes
- **Bulk Generation**: Generate multiple barcodes at once
- **Scan Integration**: Scan barcodes to retrieve item information
- **Visual Output**: SVG and PNG barcode images
- **Auto SKU Generation**: Automatic SKU creation for items without codes

### Notification System
- **Real-time Alerts**: Low stock, order updates, production notifications
- **Priority Levels**: Urgent, high, medium, low priority notifications
- **Multi-channel**: Email, SMS, push notification support (configurable)
- **User Targeting**: Send notifications to specific users or broadcast
- **Status Management**: Unread, read, dismissed status tracking
- **Auto-generation**: Automatic low stock alerts
- **Rich Data**: Include contextual data with notifications

### File Management
- **Product Images**: Upload and manage product photos
- **Document Storage**: Store specifications, manuals, certificates
- **Bulk Upload**: Upload multiple files at once
- **File Organization**: Organized storage by type and item
- **Storage Monitoring**: Track storage usage and file counts
- **Secure Access**: Controlled file access and deletion

### Data Export & Backup
- **Multiple Formats**: CSV, JSON, Excel export options
- **Flexible Filtering**: Export specific data ranges and types
- **Complete Backups**: Full database backup with metadata
- **File Inclusion**: Option to include uploaded files in backups
- **Export History**: Track and manage previous exports
- **Automated Scheduling**: Ready for scheduled backup integration

### Integration Benefits
- **Barcode Scanning**: Quick inventory lookups and stock checks
- **Proactive Alerts**: Stay informed about critical inventory levels
- **Documentation**: Keep all product-related files organized
- **Data Security**: Regular backups ensure data protection
- **Business Intelligence**: Export data for external analysis
- **Compliance**: Maintain audit trails and documentation