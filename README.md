# CiviCRM PowerMap Extension

## Interactive Stakeholder Network Visualization

PowerMap is a comprehensive CiviCRM extension that provides powerful stakeholder network visualization and analysis capabilities. It transforms your contact relationships into interactive, force-directed network diagrams that help you understand influence patterns, support levels, and strategic relationships within your network.

![Screenshot](/images/powermap_visualization.png)


## 🌟 Features

### Interactive Network Visualization
- **D3.js-powered network chart** with nodes representing stakeholders
- **Force-directed layout** that automatically organizes relationships
- **Drag-and-drop functionality** for manual positioning
- **Zoom and pan controls** for navigation
- **Color-coded nodes** based on influence levels
- **Different line styles** for relationship types

### Advanced Filtering System
- **Relationship type filter** (Reports To, Colleague, Advisor, Friend)
- **Relationship depth filter** (1st, 2nd, 3rd degree connections)
- **Influence level slider** with real-time updates
- **Support level slider** for stakeholder sentiment
- **Real-time chart updates** as filters are applied

### Stakeholder Management
- **"Add New Stakeholder" modal** with comprehensive form
- **Relationship type selection** during creation
- **Influence and support level assignment**
- **Automatic network integration** of new stakeholders

### Dashboard Analytics
- **Real-time statistics cards** showing:
  - Total stakeholders count
  - High influence stakeholders
  - Strong supporters
  - Opposition count
- **Interactive legend** for easy interpretation

### Advanced Interactions
- **Hover tooltips** with detailed stakeholder information
- **Click highlighting** of connected nodes and relationships
- **Search functionality** to find specific stakeholders
- **Keyboard shortcuts** for zoom control
- **Mobile touch support** for tablet/phone usage

### Export and Utility Features
- **CSV export functionality** for data analysis
- **Network reset and centering** controls
- **Performance optimization** for large networks
- **Auto-refresh simulation** of real-time updates

## 🎨 Design Highlights

### Modern UI/UX
- **Glassmorphism design** with blurred backgrounds
- **Gradient color schemes** for visual appeal
- **Responsive layout** that works on different screen sizes
- **Smooth animations** and transitions
- **Professional color coding** for different data types

### Accessibility Features
- **Clear visual hierarchy** with proper contrast
- **Keyboard navigation support**
- **Screen reader friendly labels**
- **Touch-friendly controls** for mobile devices

## 📊 Data Visualization Features

### Node Representation
- **Size indicates influence level** (larger = more influential)
- **Color represents influence category** (red = high, green = low)
- **Position shows relationship depth** from central contact

### Link Representation
- **Color indicates relationship type**
- **Thickness shows relationship strength**
- **Arrows show relationship direction**
- **Dynamic opacity** for focus/filtering

## 🔧 Installation

### Requirements
- CiviCRM 5.0 or later
- PHP 7.4 or later
- Modern web browser with JavaScript enabled

### Installation Steps

1. **Download the extension**
   ```bash
   cd [civicrm_extensions_directory]
   git clone https://github.com/sunilpawar/powermap.git
   ```

2. **Install via CiviCRM Admin**
  - Go to Administer > System Settings > Extensions
  - Find "PowerMap" in the list
  - Click "Install"

3. **Verify Installation**
  - Navigate to Contacts > PowerMap
  - You should see the PowerMap visualization interface

### Custom Fields Setup

The extension automatically creates the following custom fields:

- **Influence Level** (1-5 scale)
- **Support Level** (1-5 scale)
- **PowerMap Notes** (text area)
- **Network Position** (dropdown)
- **Relationship Strength** (1-3 scale)

## 🚀 Usage

### Basic Navigation

1. **Access PowerMap**
  - Navigate to Contacts > PowerMap
  - The visualization will load with your existing contact relationships

2. **Using Filters**
  - Use the sidebar filters to refine the network view
  - Adjust influence and support level sliders
  - Toggle relationship types on/off
  - Search for specific stakeholders

3. **Interacting with the Network**
  - **Hover** over nodes to see stakeholder details
  - **Click** nodes to highlight connections
  - **Drag** nodes to reposition them
  - **Zoom** using mouse wheel or control buttons

### Adding Stakeholders

1. **Click "Add New Stakeholder"** in the sidebar
2. **Select a contact** from the dropdown
3. **Set influence and support levels** (1-5 scale)
4. **Choose relationship type** (optional)
5. **Select related contact** (optional)
6. **Add notes** about the stakeholder
7. **Click "Add Stakeholder"**

### Advanced Features

#### Network Analysis
- View network statistics in the sidebar
- Identify key influencers and supporters
- Analyze relationship patterns
- Export data for external analysis

#### Keyboard Shortcuts
- **Ctrl/Cmd + Plus**: Zoom in
- **Ctrl/Cmd + Minus**: Zoom out
- **Ctrl/Cmd + 0**: Reset zoom
- **Escape**: Clear selections

## 🔌 API Reference

### PowerMap API Endpoints

#### Get Network Data
```php
$result = civicrm_api3('PowerMap', 'getnetworkdata', [
  'group_id' => 123,
  'influence_min' => 3,
  'only_relationship' => TRUE
]);
```

#### Export to CSV
```php
// Export CSV for specific contacts
$csv = civicrm_api3('PowerMap', 'exporttocsv', [
  'contact_id' => [101, 102, 103],
  'support_min' => 4
]);
```

### Custom Field API

#### Update Stakeholder Data
```php
civicrm_api3('Contact', 'create', [
  'id' => $contactId,
  'custom_influence_level' => 4,
  'custom_support_level' => 5,
  'custom_powermap_notes' => 'Key decision maker'
]);
```

## 🎯 Use Cases

### Political Campaigns
- Map voter influence networks
- Identify key endorsers and opponents
- Track relationship changes over time
- Plan outreach strategies

### Non-Profit Organizations
- Visualize donor networks
- Identify potential board members
- Map volunteer coordination
- Track stakeholder engagement

### Business Development
- Map client relationship networks
- Identify decision makers
- Track partnership opportunities
- Analyze competitive landscapes

### Community Organizing
- Map community leaders
- Identify influence patterns
- Plan coalition building
- Track issue support

## 🛠️ Customization

### Custom Relationship Types

Add custom relationship types through CiviCRM:

1. Go to Administer > Customize Data and Screens > Relationship Types
2. Create new relationship types
3. PowerMap will automatically include them in filters

### Custom Influence Calculations

Extend the influence calculation by modifying:

```php
// File: CRM/Powermap/BAO/PowerMapAnalysis.php
public static function calculateInfluenceScore($contactId) {
  // Add your custom logic here
  $customFactors = self::getCustomInfluenceFactors($contactId);
  // ... existing code
}
```

## 🔍 Troubleshooting

### Common Issues

#### Visualization Not Loading
1. Check JavaScript console for errors
2. Verify D3.js library is loading
3. Ensure custom fields are created
4. Check PHP error logs

#### Performance Issues
1. Limit the number of contacts displayed
2. Use filters to reduce network size
3. Check server memory limits
4. Optimize database queries

#### Missing Relationships
1. Verify relationships are marked as "Active"
2. Check relationship permissions
3. Ensure both contacts exist and are not deleted
4. Check relationship type configurations


## 📈 Performance Optimization

### Large Networks

For networks with 1000+ contacts:

1. **Use filters** to reduce visible nodes
4. **Use database indexes** on custom fields

## About Skvare

Skvare LLC specializes in CiviCRM development, Drupal integration, and providing technology solutions for nonprofit organizations, professional societies, membership-driven associations, and small businesses. We are committed to developing open source software that empowers our clients and the wider CiviCRM community.

**Contact Information**:
- Website: [https://skvare.com](https://skvare.com/contact)
- Email: info@skvare.com
- GitHub: [https://github.com/Skvare](https://github.com/Skvare)