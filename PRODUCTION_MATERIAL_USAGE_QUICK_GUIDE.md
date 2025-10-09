# Production Material Usage - Quick Reference Guide

## 🚀 Quick Access

**Main Dashboard**: `/production-material-usage`

**Quick Links**:
- 📊 Efficiency Analysis: `/production-material-usage/efficiency`
- 📦 Stock Impact: `/production-material-usage/stock-impact`
- 🗑️ Waste Analysis: `/production-material-usage/waste-analysis`

## 📋 Main Features

### 1. Material Usage Dashboard
**What**: Overview of all material consumption in production
**Shows**:
- Total usage records
- Quantity consumed
- Total costs
- Waste amounts
- Top used materials
- Low stock alerts

**Actions**:
- Filter by date range
- View detailed usage history
- Identify problematic materials
- Quick access to reports

### 2. Stock Impact Report
**What**: How production affects raw material inventory
**Shows**:
- Current stock levels
- Materials used in period
- Waste amounts
- Days until stockout
- Reorder recommendations

**Actions**:
- Identify critical stock
- Plan reorders
- Monitor consumption patterns
- Predict shortages

### 3. Efficiency Analysis
**What**: Compare expected vs actual material usage
**Shows**:
- Recipe requirements
- Actual usage
- Variances
- Cost differences
- Efficiency percentages

**Actions**:
- Optimize recipes
- Investigate overages
- Improve processes
- Track improvements

### 4. Waste Analysis
**What**: Track and analyze material waste
**Shows**:
- Waste by material
- Waste by product
- Daily trends
- Cost impact
- Waste percentage

**Actions**:
- Reduce waste
- Identify causes
- Set reduction goals
- Monitor progress

## 🎯 Common Workflows

### Recording Material Usage for Production

```
1. Go to Production Plans
2. Select a plan "In Progress"
3. Click "Record Material Usage"
4. Fill in:
   ├─ Select production plan item
   ├─ Enter actual quantity produced
   ├─ For each material:
   │   ├─ Select material
   │   ├─ Enter quantity used
   │   ├─ Enter waste (if any)
   │   └─ Add notes
   └─ Submit
5. System automatically:
   ├─ Updates raw material stock
   ├─ Updates product stock
   ├─ Calculates costs
   └─ Generates alerts if needed
```

### Checking Stock Impact

```
1. Navigate to Production Material Usage
2. Click "Stock Impact" button
3. Set date range (optional)
4. Review table:
   ├─ Red rows = Reorder needed
   ├─ Days until stockout
   └─ Usage patterns
5. Take action:
   ├─ Click "Reorder" for critical items
   └─ Plan production based on availability
```

### Analyzing Efficiency

```
1. Click "Efficiency Analysis"
2. Select time period
3. Review metrics:
   ├─ Expected vs Actual
   ├─ Cost variances
   └─ Material-level details
4. Identify:
   ├─ High-variance items
   ├─ Consistent overages
   └─ Improvement opportunities
5. Take action:
   ├─ Update recipes
   ├─ Train staff
   └─ Adjust processes
```

### Monitoring Waste

```
1. Click "Waste Analysis"
2. Set date range
3. Review:
   ├─ Top wasted materials
   ├─ Products with high waste
   ├─ Daily trends
   └─ Cost impact
4. Act on findings:
   ├─ Investigate root causes
   ├─ Implement solutions
   └─ Track improvements
```

## 🎨 Visual Guide

### Status Badges

| Badge | Meaning | Action |
|-------|---------|--------|
| 🔴 **Out of Stock** | Qty = 0 | Urgent reorder |
| 🔴 **Critical** | ≤ 50% min | High priority |
| 🟡 **Low Stock** | ≤ min stock | Plan reorder |
| 🟢 **Normal** | > min stock | Monitor |

### Days Until Stockout

| Color | Range | Urgency |
|-------|-------|---------|
| 🔴 Red | < 7 days | Urgent |
| 🟡 Yellow | 7-30 days | Plan ahead |
| 🟢 Green | > 30 days | Healthy |

## 💡 Pro Tips

### Daily Checks ✅
- [ ] Review low stock alerts
- [ ] Check critical materials
- [ ] Monitor waste levels
- [ ] Record usage promptly

### Weekly Reviews 📊
- [ ] Analyze efficiency trends
- [ ] Review waste patterns
- [ ] Check stock coverage
- [ ] Plan reorders

### Monthly Analysis 📈
- [ ] Compare month-over-month
- [ ] Set improvement goals
- [ ] Review all metrics
- [ ] Update recipes if needed

## 📊 Key Metrics Explained

### Material Usage
```
Total Used = Sum of all material consumption
Total Cost = Sum of (Quantity × Cost per Unit)
Usage Count = Number of usage records
```

### Stock Coverage
```
Days = Current Stock ÷ Average Daily Usage
```
- Based on last 30 days
- Shows when stock will run out
- Helps plan reorders

### Efficiency
```
Efficiency % = (Actual Used ÷ Expected) × 100

100% = Perfect
< 100% = Used less (good!)
> 100% = Used more (investigate)
```

### Waste Percentage
```
Waste % = (Waste Cost ÷ Production Cost) × 100

< 5% = Excellent
5-10% = Good
> 10% = Needs improvement
```

## 🔍 Filters & Search

### Date Range Filter
```
Default: Last 30 days
Custom: Select start & end dates
Quick Options:
  - Last 7 days
  - This month
  - Last month
  - Custom range
```

### Status Filters
- All materials
- Low stock only
- Critical only
- Normal stock only

## 📱 Quick Actions

### From Dashboard
- View material details → Click material name
- See usage history → Click usage count
- Check stock → Click "Stock Impact"
- Analyze efficiency → Click "Efficiency"
- Review waste → Click "Waste Analysis"

### From Stock Impact
- Reorder material → Click "Reorder" button
- View details → Click "View" button
- Export report → Click "Export" (if available)

### From Any Report
- Back to dashboard → "Back" button
- Change date range → Update filters
- Reset filters → "Reset" button

## ⚡ Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl/Cmd + F` | Search page |
| `Ctrl/Cmd + P` | Print |
| `Tab` | Navigate fields |
| `Enter` | Submit form |
| `Esc` | Close modal |

## 🚨 Alert Priority

### Urgent (Act Today)
- 🔴 Out of stock materials
- 🔴 Days until stockout < 3
- 🔴 Critical stock levels

### High (Act This Week)
- 🟡 Days until stockout < 7
- 🟡 Low stock levels
- 🟡 High waste materials

### Medium (Monitor)
- 🟡 Days until stockout < 30
- 🟡 Efficiency < 95%
- 🟡 Increasing waste trends

## 📋 Recording Checklist

### Before Recording
- [ ] Production is complete
- [ ] All materials accounted for
- [ ] Waste separated from production use
- [ ] Actual quantity measured

### During Recording
- [ ] Select correct production plan item
- [ ] Enter accurate quantity produced
- [ ] Record all materials used
- [ ] Separate waste entries
- [ ] Add helpful notes

### After Recording
- [ ] Verify stock updated
- [ ] Check for alerts
- [ ] Review costs
- [ ] Note any issues

## 🎓 Training Path

### Beginner (Week 1)
1. Read this quick guide
2. Tour the dashboard
3. Practice with test data
4. Record first usage with supervisor
5. Review daily alerts

### Intermediate (Month 1)
1. Master all reports
2. Understand all metrics
3. Identify patterns
4. Make recommendations
5. Train others

### Advanced (Quarter 1)
1. Optimize processes
2. Set efficiency goals
3. Lead waste reduction
4. Analyze trends
5. Drive improvements

## 📞 Quick Help

### Common Questions

**Q: Why is stock not updating?**
A: Ensure usage is saved correctly. Check Laravel logs.

**Q: Waste not tracking?**
A: Enter waste in separate field, not combined with production.

**Q: Days until stockout shows N/A?**
A: Need more usage history. Will calculate after a few records.

**Q: Efficiency over 100%?**
A: Used more than recipe requires. Investigate why.

**Q: How often should I record?**
A: Immediately after each production run for accuracy.

## 🔗 Quick Links

### Documentation
- [Full README](PRODUCTION_MATERIAL_USAGE_README.md)
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md)
- [Installation Guide](INSTALLATION_CHECKLIST.md)

### Dashboards
- [Production Dashboard](production-dashboard)
- [Material Usage](production-material-usage)
- [Raw Materials](raw-materials)
- [Production Plans](production-plans)

## 📊 Sample Dashboard View

```
┌─────────────────────────────────────────────────┐
│ Production Material Usage                        │
│ [Efficiency] [Stock Impact] [Waste Analysis]    │
└─────────────────────────────────────────────────┘

┌──────────┬──────────┬──────────┬──────────┐
│ Total    │ Quantity │ Total    │ Waste    │
│ Records  │ Used     │ Cost     │ Cost     │
│ 150      │ 5,234    │ $12,450  │ $890     │
└──────────┴──────────┴──────────┴──────────┘

🚨 3 materials at critical stock level

Top Used Materials (By Cost)
───────────────────────────────────────
Material      Used    Cost      Status
Flour        450kg   $900      🟢 Normal
Sugar        230kg   $460      🟡 Low
Butter       156kg   $780      🔴 Critical
...
```

## ✅ Best Practices

1. ✅ **Record Immediately**: Don't wait until end of day
2. ✅ **Be Accurate**: Measure quantities precisely
3. ✅ **Separate Waste**: Track waste separately
4. ✅ **Add Notes**: Document unusual situations
5. ✅ **Review Daily**: Check alerts every morning
6. ✅ **Act Quickly**: Don't delay on critical alerts
7. ✅ **Track Trends**: Look for patterns monthly
8. ✅ **Continuous Improvement**: Always seek to reduce waste

## 🎯 Performance Goals

### Individual
- Record usage within 1 hour of production
- Maintain 98%+ accuracy
- Respond to alerts same day
- Zero stockouts
- Reduce personal waste 5%

### Team
- 100% on-time recording
- 95% efficiency average
- < 5% waste rate
- Zero critical stockouts
- 10% cost reduction

---

**Remember**: Good data in = Good decisions out!

**Questions?** Check [full documentation](PRODUCTION_MATERIAL_USAGE_README.md) or contact support.

---

**Version**: 1.0  
**Print this guide**: Keep it at your workstation  
**Last Updated**: December 2024
