/**
 * js/powermap.js - Complete Power Mapping JavaScript Implementation
 */

(function($, _, d3) {
  'use strict';

  var PowerMap = function(container, options) {
    this.container = container;
    this.options = $.extend({
      width: 800,
      height: 600,
      margin: { top: 40, right: 40, bottom: 60, left: 60 }
    }, options);

    this.stakeholders = [];
    this.filteredStakeholders = [];
    this.svg = null;
    this.tooltip = null;
    this.selectedStakeholder = null;
    this.dragBehavior = null;

    this.init();
  };

  PowerMap.prototype.init = function() {
    this.createSVG();
    this.createTooltip();
    this.createScales();
    this.drawAxes();
    this.drawQuadrantLabels();
    this.setupDragBehavior();
    this.loadStakeholders();
    this.bindEvents();
    this.setupKeyboardHandlers();
  };

  PowerMap.prototype.createSVG = function() {
    // Clear existing SVG
    d3.select(this.container).selectAll('*').remove();

    this.svg = d3.select(this.container)
      .append('svg')
      .attr('class', 'power-map-svg')
      .attr('width', this.options.width)
      .attr('height', this.options.height);

    // Add background
    this.svg.append('rect')
      .attr('width', this.options.width)
      .attr('height', this.options.height)
      .attr('fill', '#fafafa')
      .attr('class', 'map-background');

    this.chartArea = this.svg.append('g')
      .attr('transform', `translate(${this.options.margin.left}, ${this.options.margin.top})`);

    this.chartWidth = this.options.width - this.options.margin.left - this.options.margin.right;
    this.chartHeight = this.options.height - this.options.margin.top - this.options.margin.bottom;

    // Add quadrant backgrounds
    this.drawQuadrantBackgrounds();
  };

  PowerMap.prototype.createTooltip = function() {
    // Remove existing tooltip
    d3.select('body').select('.powermap-tooltip').remove();

    this.tooltip = d3.select('body').append('div')
      .attr('class', 'tooltip powermap-tooltip')
      .style('position', 'absolute')
      .style('background', 'rgba(0, 0, 0, 0.9)')
      .style('color', 'white')
      .style('padding', '8px 12px')
      .style('border-radius', '4px')
      .style('font-size', '12px')
      .style('pointer-events', 'none')
      .style('z-index', '1000')
      .style('max-width', '250px')
      .style('box-shadow', '0 4px 8px rgba(0,0,0,0.3)')
      .style('display', 'none');
  };

  PowerMap.prototype.createScales = function() {
    // Support scale: -2 to +2 (Strong Opposition to Strong Support)
    this.xScale = d3.scaleLinear()
      .domain([-2.5, 2.5])
      .range([0, this.chartWidth]);

    // Influence scale: 0 to 3 (None to High)
    this.yScale = d3.scaleLinear()
      .domain([0, 3.5])
      .range([this.chartHeight, 0]);

    // Color scale for influence levels
    this.colorScale = d3.scaleOrdinal()
      .domain(['low', 'medium', 'high'])
      .range(['#20c997', '#fd7e14', '#dc3545']);

    // Size scale based on importance
    this.sizeScale = d3.scaleOrdinal()
      .domain(['low', 'medium', 'high'])
      .range([20, 25, 30]);
  };

  PowerMap.prototype.drawQuadrantBackgrounds = function() {
    var quadrants = [
      { x: this.chartWidth/2, y: 0, width: this.chartWidth/2, height: this.chartHeight/2, class: 'champions-bg', opacity: 0.05 },
      { x: 0, y: 0, width: this.chartWidth/2, height: this.chartHeight/2, class: 'targets-bg', opacity: 0.05 },
      { x: 0, y: this.chartHeight/2, width: this.chartWidth/2, height: this.chartHeight/2, class: 'monitor-bg', opacity: 0.05 },
      { x: this.chartWidth/2, y: this.chartHeight/2, width: this.chartWidth/2, height: this.chartHeight/2, class: 'grassroots-bg', opacity: 0.05 }
    ];

    this.chartArea.selectAll('.quadrant-bg')
      .data(quadrants)
      .enter()
      .append('rect')
      .attr('class', d => `quadrant-bg ${d.class}`)
      .attr('x', d => d.x)
      .attr('y', d => d.y)
      .attr('width', d => d.width)
      .attr('height', d => d.height)
      .attr('fill', '#e9ecef')
      .attr('opacity', d => d.opacity);
  };

  PowerMap.prototype.drawAxes = function() {
    // Draw grid lines
    var xTicks = this.xScale.ticks(10);
    var yTicks = this.yScale.ticks(8);

    // Vertical grid lines
    this.chartArea.selectAll('.grid-line-x')
      .data(xTicks)
      .enter()
      .append('line')
      .attr('class', 'grid-line grid-line-x')
      .attr('x1', d => this.xScale(d))
      .attr('x2', d => this.xScale(d))
      .attr('y1', 0)
      .attr('y2', this.chartHeight)
      .attr('stroke', '#e0e0e0')
      .attr('stroke-width', 1)
      .attr('stroke-dasharray', '2,2');

    // Horizontal grid lines
    this.chartArea.selectAll('.grid-line-y')
      .data(yTicks)
      .enter()
      .append('line')
      .attr('class', 'grid-line grid-line-y')
      .attr('x1', 0)
      .attr('x2', this.chartWidth)
      .attr('y1', d => this.yScale(d))
      .attr('y2', d => this.yScale(d))
      .attr('stroke', '#e0e0e0')
      .attr('stroke-width', 1)
      .attr('stroke-dasharray', '2,2');

    // Main axes
    // X-axis (support level)
    this.chartArea.append('line')
      .attr('class', 'axis axis-x')
      .attr('x1', 0)
      .attr('x2', this.chartWidth)
      .attr('y1', this.yScale(0))
      .attr('y2', this.yScale(0))
      .attr('stroke', '#333')
      .attr('stroke-width', 2);

    // Y-axis (influence level)
    this.chartArea.append('line')
      .attr('class', 'axis axis-y')
      .attr('x1', this.xScale(0))
      .attr('x2', this.xScale(0))
      .attr('y1', 0)
      .attr('y2', this.chartHeight)
      .attr('stroke', '#333')
      .attr('stroke-width', 2);

    // Axis labels
    this.svg.append('text')
      .attr('class', 'axis-label axis-label-x')
      .attr('x', this.options.width / 2)
      .attr('y', this.options.height - 10)
      .attr('text-anchor', 'middle')
      .attr('font-size', '14px')
      .attr('font-weight', 'bold')
      .attr('fill', '#333')
      .text('Support Level →');

    this.svg.append('text')
      .attr('class', 'axis-label axis-label-y')
      .attr('transform', `translate(15, ${this.options.height / 2}) rotate(-90)`)
      .attr('text-anchor', 'middle')
      .attr('font-size', '14px')
      .attr('font-weight', 'bold')
      .attr('fill', '#333')
      .text('↑ Influence Level');

    // Add scale indicators
    this.addScaleIndicators();
  };

  PowerMap.prototype.addScaleIndicators = function() {
    // Support scale indicators
    var supportLabels = [
      { value: -2, label: 'Strong Opposition' },
      { value: -1, label: 'Opposition' },
      { value: 0, label: 'Neutral' },
      { value: 1, label: 'Support' },
      { value: 2, label: 'Strong Support' }
    ];

    supportLabels.forEach(item => {
      this.svg.append('text')
        .attr('x', this.options.margin.left + this.xScale(item.value))
        .attr('y', this.options.height - 35)
        .attr('text-anchor', 'middle')
        .attr('font-size', '10px')
        .attr('fill', '#666')
        .text(item.label);
    });

    // Influence scale indicators
    var influenceLabels = [
      { value: 1, label: 'Low' },
      { value: 2, label: 'Medium' },
      { value: 3, label: 'High' }
    ];

    influenceLabels.forEach(item => {
      this.svg.append('text')
        .attr('x', 35)
        .attr('y', this.options.margin.top + this.yScale(item.value))
        .attr('text-anchor', 'middle')
        .attr('font-size', '10px')
        .attr('fill', '#666')
        .attr('dominant-baseline', 'middle')
        .text(item.label);
    });
  };

  PowerMap.prototype.drawQuadrantLabels = function() {
    var quadrants = [
      {
        x: this.chartWidth * 0.75,
        y: this.chartHeight * 0.25,
        lines: ['Champions', '(High Influence +', 'High Support)'],
        class: 'champions'
      },
      {
        x: this.chartWidth * 0.25,
        y: this.chartHeight * 0.25,
        lines: ['Targets', '(High Influence +', 'Low Support)'],
        class: 'targets'
      },
      {
        x: this.chartWidth * 0.25,
        y: this.chartHeight * 0.75,
        lines: ['Monitor', '(Low Influence +', 'Low Support)'],
        class: 'monitor'
      },
      {
        x: this.chartWidth * 0.75,
        y: this.chartHeight * 0.75,
        lines: ['Grassroots', '(Low Influence +', 'High Support)'],
        class: 'grassroots'
      }
    ];

    quadrants.forEach(quadrant => {
      var labelGroup = this.chartArea.append('g')
        .attr('class', `quadrant-label-group ${quadrant.class}`)
        .attr('transform', `translate(${quadrant.x}, ${quadrant.y})`);

      var textElement = labelGroup.append('text')
        .attr('class', `quadrant-label ${quadrant.class}`)
        .attr('text-anchor', 'middle')
        .attr('font-size', '12px')
        .attr('font-weight', 'bold')
        .attr('fill', '#666')
        .attr('opacity', 0.8);

      quadrant.lines.forEach((line, i) => {
        textElement.append('tspan')
          .attr('x', 0)
          .attr('dy', i === 0 ? 0 : '1.2em')
          .text(line);
      });

      // Add background rectangle for better readability
      var bbox = textElement.node().getBBox();
      labelGroup.insert('rect', 'text')
        .attr('x', bbox.x - 5)
        .attr('y', bbox.y - 2)
        .attr('width', bbox.width + 10)
        .attr('height', bbox.height + 4)
        .attr('fill', 'rgba(255, 255, 255, 0.9)')
        .attr('rx', 4)
        .attr('stroke', '#ddd')
        .attr('stroke-width', 1);
    });
  };

  PowerMap.prototype.setupDragBehavior = function() {
    var self = this;

    this.dragBehavior = d3.drag()
      .on('start', function(event, d) {
        d3.select(this).raise();
        self.dragStarted(event, d);
      })
      .on('drag', function(event, d) {
        self.dragged(event, d);
      })
      .on('end', function(event, d) {
        self.dragEnded(event, d);
      });
  };

  PowerMap.prototype.dragStarted = function(event, d) {
    d3.select(event.sourceEvent.target.parentNode)
      .classed('dragging', true)
      .transition()
      .duration(100)
      .attr('transform',
        `translate(${event.x}, ${event.y}) scale(1.1)`
      );
  };

  PowerMap.prototype.dragged = function(event, d) {
    var x = Math.max(0, Math.min(this.chartWidth, event.x));
    var y = Math.max(0, Math.min(this.chartHeight, event.y));

    d3.select(event.sourceEvent.target.parentNode)
      .attr('transform', `translate(${x}, ${y}) scale(1.1)`);
  };

  PowerMap.prototype.dragEnded = function(event, d) {
    var x = Math.max(0, Math.min(this.chartWidth, event.x));
    var y = Math.max(0, Math.min(this.chartHeight, event.y));

    // Convert back to data coordinates
    var supportScore = this.xScale.invert(x);
    var influenceScore = this.yScale.invert(y);

    // Clamp to valid ranges
    supportScore = Math.max(-2, Math.min(2, supportScore));
    influenceScore = Math.max(0, Math.min(3, influenceScore));

    // Update stakeholder data
    d.support_score = Math.round(supportScore * 2) / 2; // Round to nearest 0.5
    d.influence_score = Math.round(influenceScore * 2) / 2;
    d.quadrant = this.calculateQuadrant(d.influence_score, d.support_score);

    // Update position with animation
    d3.select(event.sourceEvent.target.parentNode)
      .classed('dragging', false)
      .transition()
      .duration(300)
      .attr('transform',
        `translate(${this.xScale(d.support_score)}, ${this.yScale(d.influence_score)}) scale(1)`
      );

    // Save updated position to server
    this.saveStakeholderPosition(d);

    // Update sidebar and stats
    this.updateSidebar();
    this.updateStats();
  };

  PowerMap.prototype.calculateQuadrant = function(influence, support) {
    if (influence >= 2 && support >= 0.5) return 'champions';
    if (influence >= 2 && support < 0.5) return 'targets';
    if (influence < 2 && support >= 0.5) return 'grassroots';
    return 'monitor';
  };

  PowerMap.prototype.saveStakeholderPosition = function(stakeholder) {
    // Save updated position via API
    CRM.api3('Contact', 'create', {
      id: stakeholder.id,
      'custom_influence_level': this.getInfluenceLevelFromScore(stakeholder.influence_score),
      'custom_support_level': this.getSupportLevelFromScore(stakeholder.support_score),
      'custom_last_assessment_date': new Date().toISOString().split('T')[0]
    }).done(function(result) {
      CRM.alert('Position updated for ' + stakeholder.name, 'Success', 'success', {expires: 3000});
    }).fail(function(error) {
      console.error('Failed to save position:', error);
      CRM.alert('Failed to save position changes', 'Error', 'error');
    });
  };

  PowerMap.prototype.getInfluenceLevelFromScore = function(score) {
    if (score >= 2.5) return 'high';
    if (score >= 1.5) return 'medium';
    return 'low';
  };

  PowerMap.prototype.getSupportLevelFromScore = function(score) {
    if (score >= 1.5) return 'strong_support';
    if (score >= 0.5) return 'support';
    if (score >= -0.5) return 'neutral';
    if (score >= -1.5) return 'opposition';
    return 'strong_opposition';
  };

  PowerMap.prototype.loadStakeholders = function() {
    var self = this;

    // Show loading indicator
    this.showLoadingIndicator();

    // Load stakeholders via CiviCRM API
    CRM.api3('Powermap', 'get', {
      'sequential': 1,
      'options': {'limit': 0}
    }).done(function(result) {
      self.hideLoadingIndicator();
      self.stakeholders = result.values || [];
      self.filteredStakeholders = self.stakeholders.slice();
      self.processStakeholderData();
      self.updateVisualization();
      self.updateSidebar();
      self.updateStats();

      // Load saved view if exists
      self.loadSavedView();
    }).fail(function(error) {
      self.hideLoadingIndicator();
      console.error('Failed to load stakeholders:', error);
      CRM.alert('Failed to load stakeholder data. Please check your permissions and try again.', 'Error', 'error');
    });
  };

  PowerMap.prototype.processStakeholderData = function() {
    // Ensure all stakeholders have required properties
    this.stakeholders.forEach(stakeholder => {
      // Convert string values to numeric scores
      stakeholder.influence_score = this.getNumericInfluenceScore(stakeholder.influence_level);
      stakeholder.support_score = this.getNumericSupportScore(stakeholder.support_level);

      // Calculate quadrant
      stakeholder.quadrant = this.calculateQuadrant(stakeholder.influence_score, stakeholder.support_score);

      // Get engagement strategy
      stakeholder.strategy = this.getEngagementStrategy(stakeholder.quadrant);

      // Generate initials for visualization
      stakeholder.initials = this.getInitials(stakeholder.name);

      // Add position jitter to avoid overlapping
      stakeholder.support_score += (Math.random() - 0.5) * 0.1;
      stakeholder.influence_score += (Math.random() - 0.5) * 0.1;
    });
  };

  PowerMap.prototype.getNumericInfluenceScore = function(level) {
    var scores = { 'high': 3, 'medium': 2, 'low': 1 };
    return scores[level] || 1;
  };

  PowerMap.prototype.getNumericSupportScore = function(level) {
    var scores = {
      'strong_support': 2,
      'support': 1,
      'neutral': 0,
      'opposition': -1,
      'strong_opposition': -2
    };
    return scores[level] || 0;
  };

  PowerMap.prototype.showLoadingIndicator = function() {
    this.chartArea.append('g')
      .attr('class', 'loading-indicator')
      .append('text')
      .attr('x', this.chartWidth / 2)
      .attr('y', this.chartHeight / 2)
      .attr('text-anchor', 'middle')
      .attr('font-size', '16px')
      .attr('fill', '#666')
      .text('Loading stakeholders...');
  };

  PowerMap.prototype.hideLoadingIndicator = function() {
    this.chartArea.select('.loading-indicator').remove();
  };

  PowerMap.prototype.updateVisualization = function() {
    var self = this;

    // Bind data to nodes
    var nodes = this.chartArea.selectAll('.stakeholder-node')
      .data(this.filteredStakeholders, d => d.id);

    // Remove old nodes
    nodes.exit()
      .transition()
      .duration(300)
      .attr('opacity', 0)
      .remove();

    // Add new node groups
    var nodeEnter = nodes.enter()
      .append('g')
      .attr('class', 'stakeholder-node')
      .attr('opacity', 0);

    // Add circles for stakeholders
    nodeEnter.append('circle')
      .attr('class', 'stakeholder-circle')
      .attr('r', d => this.sizeScale(d.influence_level))
      .attr('fill', d => this.colorScale(d.influence_level))
      .attr('stroke', '#fff')
      .attr('stroke-width', 3)
      .attr('cursor', 'pointer');

    // Add support indicators
    nodeEnter.append('text')
      .attr('class', 'support-indicator')
      .attr('x', 22)
      .attr('y', -22)
      .attr('text-anchor', 'middle')
      .attr('font-size', '14px')
      .attr('font-weight', 'bold')
      .attr('fill', d => this.getSupportIndicatorColor(d.support_score))
      .text(d => this.getSupportIndicator(d.support_score));

    // Add initials labels
    nodeEnter.append('text')
      .attr('class', 'stakeholder-initials')
      .attr('text-anchor', 'middle')
      .attr('dy', '0.35em')
      .attr('font-size', '11px')
      .attr('font-weight', 'bold')
      .attr('fill', 'white')
      .attr('pointer-events', 'none')
      .text(d => d.initials);

    // Merge enter and update selections
    var nodeUpdate = nodeEnter.merge(nodes);

    // Animate to positions
    nodeUpdate
      .transition()
      .duration(500)
      .attr('opacity', 1)
      .attr('transform', d =>
        `translate(${this.xScale(d.support_score)}, ${this.yScale(d.influence_score)})`
      );

    // Update circle properties
    nodeUpdate.select('.stakeholder-circle')
      .transition()
      .duration(300)
      .attr('r', d => this.sizeScale(d.influence_level))
      .attr('fill', d => this.colorScale(d.influence_level));

    // Update support indicators
    nodeUpdate.select('.support-indicator')
      .transition()
      .duration(300)
      .attr('fill', d => this.getSupportIndicatorColor(d.support_score))
      .text(d => this.getSupportIndicator(d.support_score));

    // Add event handlers
    nodeUpdate
      .on('mouseover', function(event, d) {
        self.showTooltip(event, d);
        d3.select(this)
          .transition()
          .duration(200)
          .attr('transform',
            `translate(${self.xScale(d.support_score)}, ${self.yScale(d.influence_score)}) scale(1.15)`
          );
      })
      .on('mousemove', function(event, d) {
        self.moveTooltip(event);
      })
      .on('mouseout', function(event, d) {
        self.hideTooltip();
        d3.select(this)
          .transition()
          .duration(200)
          .attr('transform',
            `translate(${self.xScale(d.support_score)}, ${self.yScale(d.influence_score)}) scale(1)`
          );
      })
      .on('click', function(event, d) {
        event.stopPropagation();
        self.selectStakeholder(d);
      })
      .on('dblclick', function(event, d) {
        event.stopPropagation();
        self.openStakeholderRecord(d);
      })
      .call(this.dragBehavior);

    // Add selection highlighting
    nodeUpdate.classed('selected', d => self.selectedStakeholder && d.id === self.selectedStakeholder.id);
  };

  PowerMap.prototype.getSupportIndicator = function(score) {
    if (score >= 1) return '✓';
    if (score <= -1) return '✗';
    return '●';
  };

  PowerMap.prototype.getSupportIndicatorColor = function(score) {
    if (score >= 1) return '#28a745';
    if (score <= -1) return '#dc3545';
    return '#ffc107';
  };

  PowerMap.prototype.getInitials = function(name) {
    if (!name) return '??';
    return name.split(' ')
      .map(n => n.charAt(0))
      .join('')
      .toUpperCase()
      .substring(0, 2);
  };

  PowerMap.prototype.showTooltip = function(event, d) {
    var strategy = this.getEngagementStrategy(d.quadrant);
    var lastAssessed = d.last_assessment_date ?
      new Date(d.last_assessment_date).toLocaleDateString() : 'Never';

    var tooltipContent = `
      <strong>${d.name}</strong><br>
      <em>${d.stakeholder_type}</em><br><br>
      <strong>Influence:</strong> ${d.influence_level}<br>
      <strong>Support:</strong> ${d.support_level}<br>
      <strong>Quadrant:</strong> ${d.quadrant}<br>
      <strong>Strategy:</strong> ${strategy.strategy}<br>
      <strong>Priority:</strong> ${d.engagement_priority}<br><br>
      <strong>Last Assessed:</strong> ${lastAssessed}<br><br>
      <small>Double-click to open contact record</small>
    `;

    this.tooltip
      .style('display', 'block')
      .html(tooltipContent);

    this.moveTooltip(event);
  };

  PowerMap.prototype.moveTooltip = function(event) {
    var tooltip = this.tooltip.node();
    var tooltipRect = tooltip.getBoundingClientRect();
    var x = event.pageX + 15;
    var y = event.pageY - 10;

    // Keep tooltip within viewport
    if (x + tooltipRect.width > window.innerWidth) {
      x = event.pageX - tooltipRect.width - 15;
    }
    if (y + tooltipRect.height > window.innerHeight) {
      y = event.pageY - tooltipRect.height - 10;
    }

    this.tooltip
      .style('left', x + 'px')
      .style('top', y + 'px');
  };

  PowerMap.prototype.hideTooltip = function() {
    this.tooltip.style('display', 'none');
  };

  PowerMap.prototype.selectStakeholder = function(stakeholder) {
    this.selectedStakeholder = stakeholder;

    // Update visualization
    this.chartArea.selectAll('.stakeholder-node')
      .classed('selected', d => d.id === stakeholder.id);

    // Highlight in sidebar
    $('#stakeholder-list .stakeholder-item')
      .removeClass('selected')
      .filter(`[data-contact-id="${stakeholder.id}"]`)
      .addClass('selected');

    // Scroll to item in sidebar
    var selectedItem = $(`#stakeholder-list .stakeholder-item[data-contact-id="${stakeholder.id}"]`);
    if (selectedItem.length) {
      selectedItem[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  };

  PowerMap.prototype.openStakeholderRecord = function(stakeholder) {
    var url = CRM.url('civicrm/contact/view', {
      reset: 1,
      cid: stakeholder.id
    });
    window.open(url, '_blank');
  };

  PowerMap.prototype.getEngagementStrategy = function(quadrant) {
    var strategies = {
      'champions': {
        strategy: 'Leverage and empower',
        actions: ['Give platforms', 'Provide resources', 'Offer recognition'],
        priority: 'high'
      },
      'targets': {
        strategy: 'Persuade and convert',
        actions: ['Direct lobbying', 'Relationship building', 'Education'],
        priority: 'high'
      },
      'grassroots': {
        strategy: 'Mobilize and amplify',
        actions: ['Volunteer recruitment', 'Social media', 'Testimonials'],
        priority: 'medium'
      },
      'monitor': {
        strategy: 'Monitor but don\'t prioritize',
        actions: ['Information sharing', 'Long-term cultivation'],
        priority: 'low'
      }
    };

    return strategies[quadrant] || strategies['monitor'];
  };

  PowerMap.prototype.updateSidebar = function() {
    var self = this;
    var listContainer = $('#stakeholder-list');

    if (!listContainer.length) {
      console.warn('Stakeholder list container not found');
      return;
    }

    listContainer.empty();

    // Sort stakeholders by engagement priority and influence
    var sortedStakeholders = this.filteredStakeholders.slice().sort(function(a, b) {
      var priorityOrder = { 'high': 3, 'medium': 2, 'low': 1 };
      var aPriority = priorityOrder[a.engagement_priority] || 1;
      var bPriority = priorityOrder[b.engagement_priority] || 1;

      if (aPriority !== bPriority) {
        return bPriority - aPriority;
      }

      return b.influence_
