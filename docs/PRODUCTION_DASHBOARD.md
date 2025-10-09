# Production Dashboard Documentation

## Overview

The Production Dashboard provides a comprehensive view of completed production activities with deep integration between production plans, orders, and stock levels. It's designed to give managers real-time insights into production efficiency, cost management, and inventory status.

## Features

### 1. Production Summary Statistics

The dashboard displays key metrics at the top:

- **Total Produced**: Total quantity of all products produced in the selected period
- **Production Cost**: Actual total cost of all completed production plans
- **Cost Variance**: Difference between estimated and actual costs (with percentage)
- **Completed Plans**: Number of production plans completed with on-time completion rate

### 2. Production Efficiency Metrics

Track operational efficiency with:

- **Average Completion Time**: Average days taken to complete production plans
- **On-Time Completion Rate**: Percentage of plans completed by their planned end date
- **Average Production Efficiency**: Ratio of actual to planned production quantities
- **Cost Variance Rate**: Overall cost performance metric

### 3. Products Produced Analysis

Detailed breakdown showing:

- Product name and unit
- Total quantity produced
- Number of production runs
- Total production cost
- Average cost per unit
- Current stock level
- Stock value (quantity × unit price)
- Stock status (low/normal)

**Relationship with Stock**: 
- Directly shows how production affects inventory levels
- Highlights products with low stock after production
- Calculates inventory value based on current stock

### 4. Orders Fulfilled Through Production

Track order fulfillment with:

- Order ID and customer name
- Number of items produced for the order
- Fulfillment percentage (visual progress bar)
- Total order items vs. fulfilled items
- Links to order details

**Relationship with Orders**:
- Shows which orders were fulfilled through production
- Calculates fulfillment rate by comparing produced quantities to order quantities
- Identifies partially fulfilled orders

### 5. Stock Movement Analysis

Monitor inventory changes with:

- Product name
- Quantity produced in the period
- Current stock level
- Minimum stock level
- Stock coverage days (how long current stock will last)
- Stock status (out of stock, critical, low, normal)

**Stock Status Levels**:
- **Out of Stock**: Quantity = 0
- **Critical**: Quantity ≤ 50% of minimum stock level
- **Low**: Quantity ≤ minimum stock level
- **Normal**: Above minimum stock level

### 6. Top Performing Products

Lists top 10 products by production volume showing:
- Total quantity produced
- Current stock levels
- Number of production runs
- Stock status indicators

### 7. Recent Completed Production Plans

Displays last 10 completed plans with:
- Plan number and name
- Completion date
- Number of items
- Cost comparison (estimated vs. actual)
- Cost variance
- Quick view link

## Access and Permissions

**Route**: `/production-dashboard`

**Named Route**: `production-plans.dashboard`

**Access Level**: Admin and Staff roles (same as production plans)

## Usage

### Filtering Data

Use the date range filter to analyze specific periods:

1. Select **Start Date** and **End Date**
2. Click **Apply Filter**
3. Click **Reset** to return to last 30 days (default)

### Understanding the Metrics

#### Cost Variance
- **Negative (Green)**: Production cost less than estimated (good)
- **Positive (Yellow/Red)**: Production cost exceeded estimate (requires attention)

#### Fulfillment Percentage
- **100% (Green)**: Order fully fulfilled
- **<100% (Yellow)**: Partial fulfillment

#### Stock Coverage Days
- Shows how many days the current stock will last based on average daily usage
- Calculated from raw material usage data over last 30 days
- "N/A" indicates insufficient usage data

### Key Insights

1. **Production Efficiency**: Compare planned vs. actual quantities to identify process improvements
2. **Cost Management**: Monitor cost variances to control material costs
3. **Order Fulfillment**: Track how well production meets customer orders
4. **Inventory Planning**: Use stock status and coverage data to plan future production

## Integration Points

### With Production Plans
- Retrieves all completed production plans in date range
- Calculates totals and variances
- Links back to individual plan details

### With Orders
- Matches production plan items to orders via `order_id`
- Calculates fulfillment rates
- Provides customer context

### With Products/Stock
- Updates product quantities when production completes
- Tracks current stock levels
- Monitors minimum stock thresholds
- Calculates stock coverage

### With Raw Material Usage
- Uses usage history to calculate stock coverage days
- Provides cost data for variance analysis

## Technical Implementation

### Controller
`App\Http\Controllers\ProductionDashboardController`

Key methods:
- `index()`: Main dashboard display
- `calculateStockCoverageDays()`: Stock duration calculation
- `getStockStatus()`: Stock level classification
- `calculateAvgCompletionTime()`: Average production time
- `calculateOnTimeCompletionRate()`: Punctuality metric
- `calculateAvgEfficiency()`: Production efficiency

### View
`resources/views/production-plans/dashboard.blade.php`

Components:
- Date range filter form
- Summary cards (4 metrics)
- Efficiency metrics panel
- Multiple data tables (responsive)
- Low stock alerts
- Progress bars for order fulfillment

### Models Used
- `ProductionPlan`: Main production data
- `ProductionPlanItem`: Individual product details
- `Product`: Product and stock information
- `Order` & `OrderItem`: Order fulfillment data
- `RawMaterialUsage`: Usage history for calculations

## Database Queries Optimization

The dashboard uses eager loading to optimize performance:

```php
// Loads relationships in one query
ProductionPlan::with([
    'productionPlanItems.product', 
    'productionPlanItems.order'
])
```

## Alerts and Notifications

### Low Stock Alert
Automatically displayed when products have:
- Critical stock level (≤ 50% of minimum)
- Low stock level (≤ minimum)
- Out of stock (quantity = 0)

Shows count of affected products with link to details.

## Navigation

**From Dashboard**:
- Production Plans List
- Production Reports

**To Dashboard**:
- From Production Plans index (Dashboard button)
- Direct URL: `/production-dashboard`

## Future Enhancements

Potential additions:
1. Export to PDF/Excel functionality
2. Chart visualizations for trends
3. Comparison with previous periods
4. Email alerts for low stock
5. Production forecast based on orders
6. Machine/resource utilization tracking
7. Quality metrics integration
8. Real-time updates via WebSockets

## Related Documentation

- [Production Plans Guide](PRODUCTION_PLANS.md)
- [Order Management](ORDERS.md)
- [Stock Management](STOCK.md)
- [Production Reports](PRODUCTION_REPORTS.md)

## Troubleshooting

### No Data Displayed
- Ensure production plans have been completed
- Check date range includes completed plans
- Verify actual_end_date is set on plans

### Incorrect Stock Coverage
- Requires raw material usage data
- Need at least some usage history
- Returns "N/A" if no usage data available

### Order Fulfillment Shows 0%
- Verify order_id is set on production plan items
- Check order items exist for the order
- Ensure product_id matches between production and order

## Support

For issues or questions:
1. Check application logs: `storage/logs/laravel.log`
2. Verify database relationships
3. Contact development team

---

**Last Updated**: December 2024
**Version**: 1.0
