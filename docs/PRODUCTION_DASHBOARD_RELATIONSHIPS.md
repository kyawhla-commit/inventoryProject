# Production Dashboard - Data Relationships

## Overview Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    PRODUCTION DASHBOARD                         │
│                                                                 │
│  Shows: Completed Production + Order Status + Stock Levels     │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ Aggregates data from
                              ▼
           ┌──────────────────────────────────────┐
           │    PRODUCTION PLANS (completed)       │
           │                                       │
           │  - plan_number                       │
           │  - status = 'completed'              │
           │  - actual_end_date                   │
           │  - total_estimated_cost              │
           │  - total_actual_cost                 │
           └──────────────────────────────────────┘
                              │
                              │ has many
                              ▼
           ┌──────────────────────────────────────┐
           │    PRODUCTION PLAN ITEMS              │
           │                                       │
           │  - product_id          ────────┐     │
           │  - order_id (optional) ────┐   │     │
           │  - planned_quantity        │   │     │
           │  - actual_quantity         │   │     │
           │  - estimated_material_cost │   │     │
           │  - actual_material_cost    │   │     │
           └────────────────────────────│───│─────┘
                                        │   │
                      ┌─────────────────┘   └──────────────────┐
                      │                                         │
                      ▼                                         ▼
      ┌───────────────────────────┐            ┌────────────────────────────┐
      │       ORDERS              │            │       PRODUCTS             │
      │                           │            │                            │
      │  - id                    │            │  - id                      │
      │  - customer_id           │            │  - name                    │
      │  - order_date            │            │  - quantity (stock) ◄──────┤
      │  - total_amount          │            │  - minimum_stock_level     │
      │  - status                │            │  - price                   │
      └───────────────────────────┘            │  - unit                    │
                      │                        └────────────────────────────┘
                      │ has many                              │
                      ▼                                       │ uses
      ┌───────────────────────────┐                          ▼
      │    ORDER ITEMS            │            ┌────────────────────────────┐
      │                           │            │   RAW MATERIAL USAGE       │
      │  - order_id              │            │                            │
      │  - product_id            │            │  - product_id              │
      │  - quantity (ordered)     │            │  - quantity_used           │
      │  - price                 │            │  - usage_date              │
      └───────────────────────────┘            │  - usage_type              │
                                               └────────────────────────────┘
```

## Relationship Details

### 1. Production → Stock Relationship

```
PRODUCTION PLAN ITEMS ──┬─→ PRODUCT
                        │
                        └─→ Updates product.quantity when plan completed
```

**How it works**:
1. Production plan is marked as "completed"
2. System loops through all production plan items
3. For each item, adds `actual_quantity` to `product.quantity`
4. Dashboard displays current `product.quantity` (stock level)

**Example**:
```
Production Plan Item:
- product_id: 5 (Chocolate Cake)
- actual_quantity: 100

Product (before):
- quantity: 50

Product (after completion):
- quantity: 150 (50 + 100)

Dashboard shows:
- Produced: 100 units
- Current Stock: 150 units
- Stock Status: Normal (if above minimum)
```

### 2. Production → Order Relationship

```
PRODUCTION PLAN ITEMS ──→ ORDER ID ──→ ORDER ──→ CUSTOMER
                                         │
                                         └─→ ORDER ITEMS
```

**How it works**:
1. Production plan items can have `order_id` field set
2. Links production to specific customer orders
3. Dashboard groups production by order
4. Compares produced quantities to order quantities

**Example**:
```
Order #123:
- Customer: ABC Corp
- Order Items:
  * 50 units of Product A
  * 30 units of Product B

Production Plan Items:
- Item 1: Product A, qty 50, order_id: 123
- Item 2: Product B, qty 30, order_id: 123

Dashboard shows:
- Order #123 for ABC Corp
- 2 of 2 items fulfilled
- 100% fulfillment rate
```

### 3. Stock Movement Analysis

```
PRODUCT ──┬─→ Current quantity (from products table)
          │
          ├─→ Produced quantity (from production_plan_items)
          │
          ├─→ Minimum stock level (from products table)
          │
          └─→ Usage history (from raw_material_usage)
                  │
                  └─→ Calculates coverage days
```

**How it works**:
1. Gets current stock from `products.quantity`
2. Sums production from `production_plan_items.actual_quantity`
3. Compares to `products.minimum_stock_level`
4. Calculates average daily usage from `raw_material_usage`
5. Determines stock coverage: `current stock ÷ daily usage`

**Example**:
```
Product: Vanilla Cake

From products table:
- current quantity: 120
- minimum_stock_level: 50

From production_plan_items (last 30 days):
- total produced: 200

From raw_material_usage (last 30 days):
- average daily usage: 8 units

Dashboard calculates:
- Produced: 200 units
- Current Stock: 120 units
- Minimum: 50 units
- Coverage: 120 ÷ 8 = 15 days
- Status: Normal (above minimum)
```

## Data Flow

### When Production Plan is Completed

```
1. User clicks "Complete Plan" button
   │
   ├─→ Updates production_plan.status = 'completed'
   │
   ├─→ Sets production_plan.actual_end_date = now()
   │
   └─→ For each production_plan_item:
       │
       ├─→ Gets actual_quantity or uses planned_quantity
       │
       └─→ Updates product.quantity += actual_quantity
```

### When Dashboard is Viewed

```
1. User accesses /production-dashboard
   │
   ├─→ Loads completed plans in date range
   │
   ├─→ Eager loads related data:
   │   ├─→ production_plan_items
   │   ├─→ products
   │   ├─→ orders
   │   └─→ customers
   │
   ├─→ Groups and aggregates data:
   │   ├─→ By product (for production summary)
   │   ├─→ By order (for fulfillment analysis)
   │   └─→ By plan (for efficiency metrics)
   │
   └─→ Calculates metrics:
       ├─→ Stock status
       ├─→ Coverage days
       ├─→ Fulfillment rates
       └─→ Cost variances
```

## Database Relationships

### Model Relationships

**ProductionPlan Model**:
```php
hasMany(ProductionPlanItem::class)
belongsTo(User::class, 'created_by')
belongsTo(User::class, 'approved_by')
```

**ProductionPlanItem Model**:
```php
belongsTo(ProductionPlan::class)
belongsTo(Product::class)
belongsTo(Recipe::class)
belongsTo(Order::class)  // ← Key for order relationship
```

**Product Model**:
```php
hasMany(ProductionPlanItem::class)
hasMany(RawMaterialUsage::class)
belongsTo(Category::class)
```

**Order Model**:
```php
hasMany(OrderItem::class)
hasMany(ProductionPlanItem::class)  // ← Through order_id
belongsTo(Customer::class)
```

## Key Queries

### Get Products Produced with Stock

```php
ProductionPlanItem::with(['product', 'productionPlan'])
    ->whereHas('productionPlan', function($query) {
        $query->where('status', 'completed');
    })
    ->get()
    ->groupBy('product_id')
    ->map(function ($items) {
        $product = $items->first()->product;
        return [
            'total_produced' => $items->sum('actual_quantity'),
            'current_stock' => $product->quantity,  // ← Stock relationship
            'stock_status' => getStockStatus($product),
        ];
    });
```

### Get Orders Fulfilled

```php
ProductionPlanItem::with(['order.customer', 'order.items'])
    ->whereNotNull('order_id')  // ← Has order relationship
    ->whereHas('productionPlan', function($query) {
        $query->where('status', 'completed');
    })
    ->get()
    ->groupBy('order_id')
    ->map(function ($items) {
        // Calculate fulfillment percentage
        $orderItems = $items->first()->order->items;
        // Compare production to order quantities
    });
```

### Get Stock Coverage

```php
RawMaterialUsage::where('product_id', $productId)
    ->where('usage_date', '>=', now()->subDays(30))
    ->avg('quantity_used');  // Average daily usage

$coverageDays = $product->quantity / $avgDailyUsage;
```

## Integration Points

### 1. Production Completion → Stock Update

**File**: `ProductionPlanController::complete()`

```php
DB::transaction(function () use ($productionPlan) {
    $productionPlan->update(['status' => 'completed']);
    
    foreach ($productionPlan->productionPlanItems as $planItem) {
        $quantityToAdd = $planItem->actual_quantity ?? $planItem->planned_quantity;
        $planItem->product->increment('quantity', $quantityToAdd);
        // ↑ This is where stock is updated
    }
});
```

### 2. Production Planning → Order Linking

**File**: `production-plans/create.blade.php` or `edit.blade.php`

```javascript
// When creating/editing production plan item:
{
    product_id: selectedProduct,
    order_id: selectedOrder,  // ← Links to order
    planned_quantity: quantity
}
```

### 3. Dashboard → Data Retrieval

**File**: `ProductionDashboardController::index()`

```php
$completedPlans = ProductionPlan::with([
    'productionPlanItems.product',      // ← Stock data
    'productionPlanItems.order.customer' // ← Order data
])
->where('status', 'completed')
->get();
```

## Visual Representation of Data Flow

### Stock Level Calculation

```
START: Product X has 50 units
  │
  ├─→ Production Plan 1 completes:
  │   └─→ Produced 100 units of Product X
  │       └─→ Stock becomes: 50 + 100 = 150 units
  │
  ├─→ Production Plan 2 completes:
  │   └─→ Produced 75 units of Product X  
  │       └─→ Stock becomes: 150 + 75 = 225 units
  │
  └─→ Dashboard shows:
      ├─→ Total Produced (Plans 1 & 2): 175 units
      ├─→ Current Stock: 225 units
      └─→ Stock Status: Normal (above minimum)
```

### Order Fulfillment Calculation

```
Order #456: 100 units Product A, 50 units Product B
  │
  ├─→ Production Plan Item 1:
  │   ├─→ Product A: 100 units
  │   └─→ order_id: 456  ◄─── Links to order
  │
  ├─→ Production Plan Item 2:
  │   ├─→ Product B: 50 units
  │   └─→ order_id: 456  ◄─── Links to same order
  │
  └─→ Dashboard calculates:
      ├─→ Order Items: 2
      ├─→ Fulfilled Items: 2
      └─→ Fulfillment Rate: 2/2 = 100%
```

## Key Metrics Calculation

### 1. Cost Variance

```
Estimated Cost (from plan items) = $5,000
Actual Cost (from completed items) = $5,200
Variance = $5,200 - $5,000 = $200 (over budget)
Variance % = ($200 / $5,000) × 100 = 4% over
```

### 2. Stock Coverage

```
Product Current Stock = 120 units
Average Daily Usage = 8 units/day
Coverage = 120 / 8 = 15 days
```

### 3. Production Efficiency

```
Planned Quantity = 100 units
Actual Quantity = 95 units
Efficiency = (95 / 100) × 100 = 95%
```

### 4. Order Fulfillment

```
Order has 5 items:
- Item 1: Ordered 10, Produced 10 ✓
- Item 2: Ordered 20, Produced 20 ✓
- Item 3: Ordered 15, Produced 15 ✓
- Item 4: Ordered 30, Produced 25 ✗
- Item 5: Ordered 40, Produced 40 ✓

Fulfilled = 4 out of 5 = 80%
```

## Summary

The Production Dashboard creates a comprehensive view by:

1. **Tracking Production** → Records what was manufactured
2. **Updating Stock** → Adds production to inventory
3. **Linking Orders** → Shows which orders were fulfilled
4. **Calculating Metrics** → Provides insights on efficiency
5. **Monitoring Status** → Alerts on low stock situations

All data flows naturally from the production process through to stock and order management, providing a unified view of manufacturing operations.

---

**Note**: All relationships are maintained through foreign keys and are enforced at the database level for data integrity.
