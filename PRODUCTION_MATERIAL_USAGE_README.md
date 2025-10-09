# Production Material Usage System

## 🎯 Overview

A comprehensive material usage tracking system that integrates raw materials with production processes and stock management. This system provides real-time visibility into material consumption, waste tracking, and stock impact analysis.

## ✨ Key Features

### 1. Production Material Usage Dashboard
- **Summary Statistics**: Total records, quantities used, costs, and waste
- **Top Used Materials**: Most consumed materials by cost
- **Low Stock Alerts**: Automatic warnings for materials below minimum levels
- **Recent Usage History**: Chronological list of all material consumption
- **Date Range Filtering**: Analyze any time period

### 2. Stock Impact Analysis
- **Current Stock Levels**: Real-time inventory status
- **Usage Tracking**: Materials consumed in production
- **Waste Monitoring**: Separate tracking of waste/loss
- **Days Until Stockout**: Predictive analytics based on usage patterns
- **Reorder Alerts**: Automatic identification of materials needing replenishment
- **Stock Status Classification**: Out of Stock, Critical, Low, Normal

### 3. Material Efficiency Analysis
- **Expected vs Actual Comparison**: Recipe requirements vs actual usage
- **Variance Tracking**: Identify over/under consumption
- **Cost Analysis**: Track cost performance by material
- **Efficiency Metrics**: Calculate material utilization rates
- **Production Plan Comparison**: Per-plan efficiency breakdown

### 4. Waste Analysis
- **Waste by Material**: Track which materials are wasted most
- **Waste by Product**: Identify problematic products
- **Daily Waste Trends**: Monitor waste patterns over time
- **Cost Impact**: Calculate financial loss from waste
- **Waste Percentage**: Compare waste to production usage

### 5. Requirements Comparison
- **Planned vs Actual**: Compare recipe requirements to actual usage
- **Material-by-Material Breakdown**: Detailed variance for each material
- **Batch Tracking**: Link usage to specific production batches
- **Cost Variance**: Track cost differences

## 📊 Data Relationships

```
Production Plans
    ├─→ Production Plan Items
    │       ├─→ Products
    │       ├─→ Recipes
    │       └─→ Orders
    │
    └─→ Raw Material Usage
            ├─→ Raw Materials (Stock Update)
            ├─→ Products (For tracking)
            ├─→ Orders (Fulfillment)
            └─→ Usage Type (production, waste, etc.)
```

## 🚀 Usage Workflows

### Recording Material Usage for Production

1. **Navigate**: Production Plans → Select Plan → "Record Material Usage"
2. **Select Item**: Choose which production plan item you're recording for
3. **Enter Actual Quantity**: Quantity produced
4. **Record Materials**:
   - Select raw material
   - Enter quantity used
   - Enter waste quantity (if any)
   - Add notes
5. **Submit**: System automatically:
   - Updates raw material stock
   - Calculates costs
   - Records waste separately
   - Updates product stock
   - Updates production plan costs

### Analyzing Stock Impact

1. **Navigate**: Production Material Usage → "Stock Impact"
2. **Select Date Range**: Choose period to analyze
3. **Review Metrics**:
   - Materials at risk
   - Reorder recommendations
   - Usage patterns
   - Days until stockout
4. **Take Action**:
   - Click "Reorder" for critical materials
   - View detailed material information
   - Plan production based on availability

### Reviewing Efficiency

1. **Navigate**: Production Material Usage → "Efficiency Analysis"
2. **Select Period**: Choose date range
3. **Analyze Results**:
   - Expected vs Actual usage
   - Cost variances
   - Material efficiency
   - Problematic items
4. **Optimize**:
   - Adjust recipes if needed
   - Investigate high variances
   - Improve processes

### Monitoring Waste

1. **Navigate**: Production Material Usage → "Waste Analysis"
2. **Review Reports**:
   - Waste by material
   - Waste by product
   - Daily trends
   - Cost impact
3. **Reduce Waste**:
   - Identify patterns
   - Address root causes
   - Track improvements

## 📁 Files Structure

### Controllers
```
app/Http/Controllers/
└── ProductionMaterialUsageController.php
```

### Views
```
resources/views/production-material-usage/
├── index.blade.php              (Main dashboard)
├── stock-impact.blade.php       (Stock analysis)
├── efficiency.blade.php         (Efficiency metrics)
├── waste-analysis.blade.php     (Waste reports)
└── requirements-comparison.blade.php (Plan comparison)
```

### Routes
```php
// Production Material Usage Routes
Route::get('production-material-usage', ...);
Route::get('production-material-usage/efficiency', ...);
Route::get('production-material-usage/stock-impact', ...);
Route::get('production-material-usage/waste-analysis', ...);
Route::get('production-plans/{productionPlan}/record-material-usage', ...);
Route::post('production-plans/{productionPlan}/store-material-usage', ...);
Route::get('production-plans/{productionPlan}/requirements-comparison', ...);
```

## 🔑 Key Metrics

### Stock Status Classification
- **Out of Stock**: Quantity = 0 (🔴 Critical)
- **Critical**: ≤ 50% of minimum stock (🔴 Urgent)
- **Low**: ≤ minimum stock level (🟡 Plan Reorder)
- **Normal**: > minimum stock level (🟢 Healthy)

### Usage Types
- **Production**: Materials used in manufacturing
- **Waste**: Materials lost/damaged in production
- **Adjustment**: Stock corrections
- **Testing**: Quality assurance consumption
- **Maintenance**: Equipment-related usage
- **Other**: Miscellaneous consumption

### Efficiency Calculation
```
Efficiency % = (Actual Used / Expected from Recipe) × 100

- 100% = Perfect efficiency
- < 100% = Used less than expected (good)
- > 100% = Used more than expected (investigate)
```

### Days Until Stockout
```
Days = Current Stock / Average Daily Usage

Based on last 30 days of consumption data
```

## 🎨 UI Components

### Dashboard Cards
1. **Total Records**: Count of usage entries
2. **Total Quantity Used**: Sum of all materials consumed
3. **Total Cost**: Financial value of materials used
4. **Waste Cost**: Value of wasted materials

### Stock Impact Cards
1. **Materials Tracked**: Number of materials analyzed
2. **Reorder Needed**: Count requiring replenishment
3. **Total Used**: Quantity consumed in period
4. **Total Waste**: Quantity wasted in period

### Color Coding
- 🔴 Red: Critical/Urgent action needed
- 🟡 Yellow: Warning/Low stock
- 🟢 Green: Normal/Healthy
- 🔵 Blue: Informational

## 🔐 Access Control

**Required Roles**: Admin, Staff

**Permissions**:
- View material usage
- Record usage for production
- Analyze efficiency
- View waste reports
- Generate stock impact reports

## 📈 Business Benefits

### For Production Managers
- ✅ Real-time material consumption tracking
- ✅ Identify inefficiencies quickly
- ✅ Reduce waste and costs
- ✅ Better resource planning

### For Inventory Managers
- ✅ Predictive stock alerts
- ✅ Accurate reorder points
- ✅ Usage pattern analysis
- ✅ Stock optimization

### For Finance/Management
- ✅ Cost control and tracking
- ✅ Waste reduction opportunities
- ✅ Efficiency metrics
- ✅ Budget accuracy improvement

## 🔧 Integration Points

### With Production Plans
- Automatically calculates expected materials
- Records actual usage per plan item
- Updates costs in real-time
- Links to batch numbers

### With Raw Materials
- Updates stock levels automatically
- Triggers low stock alerts
- Tracks supplier information
- Maintains usage history

### With Products
- Links material usage to specific products
- Tracks production output
- Calculates true production costs
- Updates finished goods inventory

### With Orders
- Associates material usage with customer orders
- Tracks order-specific costs
- Improves order profitability analysis

## 💡 Best Practices

### Recording Usage
1. ✅ Record usage immediately after production
2. ✅ Be accurate with quantities
3. ✅ Separate waste from production usage
4. ✅ Add detailed notes for unusual situations
5. ✅ Use batch numbers consistently

### Managing Stock
1. ✅ Check stock impact report daily
2. ✅ Act on reorder alerts promptly
3. ✅ Monitor days until stockout
4. ✅ Adjust minimum stock levels seasonally
5. ✅ Review supplier performance

### Reducing Waste
1. ✅ Review waste reports weekly
2. ✅ Investigate high-waste materials
3. ✅ Train staff on waste reduction
4. ✅ Track waste reduction progress
5. ✅ Set waste reduction goals

### Improving Efficiency
1. ✅ Compare expected vs actual monthly
2. ✅ Update recipes when consistently over/under
3. ✅ Document efficiency improvements
4. ✅ Share best practices across teams
5. ✅ Reward efficiency gains

## 📊 Reports Available

### 1. Material Usage Dashboard
- Summary statistics
- Top materials by cost
- Recent usage history
- Low stock alerts

### 2. Stock Impact Report
- Current stock levels
- Usage in period
- Waste tracking
- Days until stockout
- Reorder recommendations

### 3. Efficiency Analysis
- Expected vs actual comparison
- Material-level variances
- Cost analysis
- Production plan breakdown

### 4. Waste Analysis
- Waste by material
- Waste by product
- Daily trends
- Cost impact
- Waste percentage

### 5. Requirements Comparison
- Recipe vs actual usage
- Per-material breakdown
- Variance tracking
- Batch-level analysis

## 🚨 Alerts and Notifications

### Auto-Generated Alerts
- 🔴 **Critical Stock**: Material below 50% of minimum
- 🟡 **Low Stock**: Material at or below minimum
- 🔴 **Out of Stock**: Material quantity = 0
- ⚠️ **High Waste**: Waste exceeds threshold
- 📊 **Efficiency Issues**: Large variance from expected

## 🔍 Troubleshooting

### Issue: Stock not updating
**Solution**:
- Verify raw_material_id is correct
- Check updateRawMaterialStock() method
- Review database transactions
- Check for validation errors

### Issue: Efficiency calculations incorrect
**Solution**:
- Ensure recipes are up to date
- Verify planned quantity is accurate
- Check material requirements calculation
- Review actual usage records

### Issue: Waste not tracking separately
**Solution**:
- Confirm usage_type is set to 'waste'
- Check separate waste entries are created
- Review store method logic
- Verify form data submission

### Issue: Days until stockout shows N/A
**Solution**:
- Need more usage history (minimum 1 record)
- Check average daily usage calculation
- Ensure usage_date is within last 30 days
- Review RawMaterialUsage records

## 📚 Related Documentation

- [Production Dashboard](README_PRODUCTION_DASHBOARD.md)
- [Raw Material Usage](docs/RAW_MATERIAL_USAGE.md)
- [Production Plans](docs/PRODUCTION_PLANS.md)
- [Stock Management](docs/STOCK_MANAGEMENT.md)

## 🎓 Training Resources

### For New Users
1. Review this README
2. Watch dashboard walkthrough
3. Practice with test data
4. Record first usage with supervisor
5. Review reports daily for one week

### For Power Users
1. Master all report types
2. Learn efficiency optimization
3. Set up custom alerts
4. Train others on best practices
5. Contribute to process improvements

## 🎯 Success Metrics

Track these to measure effectiveness:
- **Waste Reduction**: Target 5-10% decrease
- **Stock Accuracy**: Maintain 95%+ accuracy
- **Stockouts**: Reduce by 50%
- **Cost Variance**: Keep within ±10%
- **Efficiency**: Improve to 98%+
- **Recording Time**: < 5 minutes per production

## 📞 Support

**For Issues**:
1. Check this documentation
2. Review troubleshooting section
3. Check Laravel logs
4. Contact system administrator

**For Training**:
- Schedule walkthrough session
- Review training videos
- Practice with test environment
- Ask questions in team meetings

---

**Version**: 1.0
**Last Updated**: December 2024
**Status**: ✅ Ready for Production Use

---

*This system significantly improves material tracking, reduces waste, and optimizes inventory management for manufacturing operations.*
