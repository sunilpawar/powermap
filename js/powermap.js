/**
 * js/powermap.js - Complete Power Mapping JavaScript Implementation
 * Enhanced with full functionality for stakeholder visualization and management
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
    this.zoom = null;
    this.simulation = null;

    this.init();
  };

  PowerMap.prototype.init = function() {
    this.createSVG();
    this.createTooltip();
    this.createScales();
    this.drawAxes();
    this.drawQuadrantLabels();
    this.setupDragBehavior();
    this.setupZoomBehavior();
    this.loadStakeholders();
    this.bindEvents();
    this.setupKeyboardHandlers();
    this.initializeSearch();
  };

  PowerMap.prototype.createSVG = function() {
    // Clear existing SVG
    d3.select(this.container).selectAll('*').remove();

    this.svg = d3.select(this.container)
      .append('svg')
      .attr('class', 'power-map-svg')
      .attr('width', this.options.width)
      .attr('height', this.options.height);

    // Add background with grid pattern
    this.svg.append('defs')
      .append('pattern')
      .attr('id', 'grid')
      .attr('width', 20)
      .attr('height', 20)
      .attr('patternUnits', 'userSpaceOnUse')
      .append('path')
      .attr('d', 'M 20 0 L 0 0 0 20')
      .attr('fill', 'none')
      .attr('stroke', '#f0f0f0')
      .attr('stroke-width', 1);

    this.svg.append('rect')
      .attr('width', this.options.width)
      .attr('height', this.options.height)
      .attr('fill', 'url(#grid)')
      .attr('class', 'map-background');

    // Create main group with zoom/pan support
    this.mainGroup = this.svg.append('g')
      .attr('class', 'main-group');

    this.chartArea = this.mainGroup.append('g')
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
      .style('padding', '12px 16px')
      .style('border-radius', '6px')
      .style('font-size', '12px')
      .style('pointer-events', 'none')
      .style('z-index', '1000')
      .style('max-width', '300px')
      .style('box-shadow', '0 4px 12px rgba(0,0,0,0.3)')
      .style('display', 'none')
      .style('font-family', 'Arial, sans-serif');
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
      .range(['#28a745', '#fd7e14', '#dc3545']);

    // Size scale based on importance
    this.sizeScale = d3.scaleOrdinal()
      .domain(['low', 'medium', 'high'])
      .range([18, 24, 30]);

    // Priority scale for stroke width
    this.priorityScale = d3.scaleOrdinal()
      .domain(['low', 'medium', 'high'])
      .range([2, 3, 4]);
  };

  PowerMap.prototype.drawQuadrantBackgrounds = function() {
    var quadrants = [
      {
        x: this.chartWidth/2,
        y: 0,
        width: this.chartWidth/2,
        height: this.chartHeight/2,
        class: 'champions-bg',
        fill: '#d4edda',
        opacity: 0.1
      },
      {
        x: 0,
        y: 0,
        width: this.chartWidth/2,
        height: this.chartHeight/2,
        class: 'targets-bg',
        fill: '#f8d7da',
        opacity: 0.1
      },
      {
        x: 0,
        y: this.chartHeight/2,
        width: this.chartWidth/2,
        height: this.chartHeight/2,
        class: 'monitor-bg',
        fill: '#e2e3e5',
        opacity: 0.1
      },
      {
        x: this.chartWidth/2,
        y: this.chartHeight/2,
        width: this.chartWidth/2,
        height: this.chartHeight/2,
        class: 'grassroots-bg',
        fill: '#d1ecf1',
        opacity: 0.1
      }
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
      .attr('fill', d => d.fill)
      .attr('opacity', d => d.opacity)
      .attr('rx', 4);
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
      .attr('stroke-dasharray', '2,2')
      .attr('opacity', 0.8);

    // Animate spinner
    spinner.transition()
      .duration(1000)
      .ease(d3.easeLinear)
      .attrTween('transform', function() {
        return d3.interpolateString(
          `rotate(0 ${this.chartWidth / 2} ${this.chartHeight / 2})`,
          `rotate(360 ${this.chartWidth / 2} ${this.chartHeight / 2})`
        );
      })
      .on('end', function repeat() {
        d3.select(this)
          .transition()
          .duration(1000)
          .ease(d3.easeLinear)
          .attrTween('transform', function() {
            return d3.interpolateString(
              `rotate(0 ${this.chartWidth / 2} ${this.chartHeight / 2})`,
              `rotate(360 ${this.chartWidth / 2} ${this.chartHeight / 2})`
            );
          })
          .on('end', repeat);
      }.bind(this));

    loadingGroup.append('text')
      .attr('x', this.chartWidth / 2)
      .attr('y', this.chartHeight / 2 + 40)
      .attr('text-anchor', 'middle')
      .attr('font-size', '16px')
      .attr('fill', '#666')
      .text('Loading stakeholders...');
  };

  PowerMap.prototype.hideLoadingIndicator = function() {
    this.chartArea.select('.loading-indicator')
      .transition()
      .duration(300)
      .attr('opacity', 0)
      .remove();
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
      .attr('transform', d => `translate(${this.xScale(d.support_score)}, ${this.yScale(d.influence_score)}) scale(0)`)
      .remove();

    // Add new node groups
    var nodeEnter = nodes.enter()
      .append('g')
      .attr('class', 'stakeholder-node')
      .attr('data-id', d => d.id)
      .attr('opacity', 0)
      .attr('transform', d => `translate(${this.xScale(d.support_score)}, ${this.yScale(d.influence_score)}) scale(0)`);

    // Add outer ring for priority indication
    nodeEnter.append('circle')
      .attr('class', 'priority-ring')
      .attr('r', d => this.sizeScale(d.influence_level) + this.priorityScale(d.engagement_priority))
      .attr('fill', 'none')
      .attr('stroke', d => this.getPriorityColor(d.engagement_priority))
      .attr('stroke-width', d => this.priorityScale(d.engagement_priority))
      .attr('opacity', 0.6);

    // Add main circles for stakeholders
    nodeEnter.append('circle')
      .attr('class', 'stakeholder-circle')
      .attr('r', d => this.sizeScale(d.influence_level))
      .attr('fill', d => this.colorScale(d.influence_level))
      .attr('stroke', '#fff')
      .attr('stroke-width', 3)
      .attr('cursor', 'pointer')
      .attr('filter', 'drop-shadow(0px 2px 4px rgba(0,0,0,0.3))');

    // Add support indicators
    nodeEnter.append('text')
      .attr('class', 'support-indicator')
      .attr('x', 0)
      .attr('y', -28)
      .attr('text-anchor', 'middle')
      .attr('font-size', '16px')
      .attr('font-weight', 'bold')
      .attr('fill', d => this.getSupportIndicatorColor(d.support_score))
      .attr('stroke', '#fff')
      .attr('stroke-width', 1)
      .text(d => this.getSupportIndicator(d.support_score));

    // Add initials labels
    nodeEnter.append('text')
      .attr('class', 'stakeholder-initials')
      .attr('text-anchor', 'middle')
      .attr('dy', '0.35em')
      .attr('font-size', d => Math.max(10, this.sizeScale(d.influence_level) / 2.5) + 'px')
      .attr('font-weight', 'bold')
      .attr('fill', 'white')
      .attr('pointer-events', 'none')
      .text(d => d.initials);

    // Add name labels (initially hidden)
    nodeEnter.append('text')
      .attr('class', 'stakeholder-name-label')
      .attr('text-anchor', 'middle')
      .attr('y', 45)
      .attr('font-size', '11px')
      .attr('font-weight', '600')
      .attr('fill', '#333')
      .attr('opacity', 0)
      .attr('pointer-events', 'none')
      .text(d => d.name);

    // Merge enter and update selections
    var nodeUpdate = nodeEnter.merge(nodes);

    // Animate to positions
    nodeUpdate
      .transition()
      .duration(750)
      .ease(d3.easeCubicOut)
      .attr('opacity', 1)
      .attr('transform', d =>
        `translate(${this.xScale(d.support_score)}, ${this.yScale(d.influence_score)}) scale(1)`
      );

    // Update circle properties
    nodeUpdate.select('.stakeholder-circle')
      .transition()
      .duration(300)
      .attr('r', d => this.sizeScale(d.influence_level))
      .attr('fill', d => this.colorScale(d.influence_level));

    // Update priority rings
    nodeUpdate.select('.priority-ring')
      .transition()
      .duration(300)
      .attr('r', d => this.sizeScale(d.influence_level) + this.priorityScale(d.engagement_priority))
      .attr('stroke', d => this.getPriorityColor(d.engagement_priority))
      .attr('stroke-width', d => this.priorityScale(d.engagement_priority));

    // Update support indicators
    nodeUpdate.select('.support-indicator')
      .transition()
      .duration(300)
      .attr('fill', d => this.getSupportIndicatorColor(d.support_score))
      .text(d => this.getSupportIndicator(d.support_score));

    // Update initials
    nodeUpdate.select('.stakeholder-initials')
      .transition()
      .duration(300)
      .attr('font-size', d => Math.max(10, this.sizeScale(d.influence_level) / 2.5) + 'px')
      .text(d => d.initials);

    // Add event handlers
    nodeUpdate
      .on('mouseover', function(event, d) {
        self.showTooltip(event, d);
        self.highlightStakeholder(d, true);
      })
      .on('mousemove', function(event, d) {
        self.moveTooltip(event);
      })
      .on('mouseout', function(event, d) {
        self.hideTooltip();
        self.highlightStakeholder(d, false);
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

    // Show/hide name labels based on zoom level
    this.updateNameLabels();
  };

  PowerMap.prototype.highlightStakeholder = function(stakeholder, highlight) {
    var nodeGroup = this.chartArea.select(`.stakeholder-node[data-id="${stakeholder.id}"]`);

    if (highlight) {
      nodeGroup
        .transition()
        .duration(200)
        .attr('transform', d =>
          `translate(${this.xScale(d.support_score)}, ${this.yScale(d.influence_score)}) scale(1.2)`
        );

      nodeGroup.select('.stakeholder-name-label')
        .transition()
        .duration(200)
        .attr('opacity', 1);
    } else {
      nodeGroup
        .transition()
        .duration(200)
        .attr('transform', d =>
          `translate(${this.xScale(d.support_score)}, ${this.yScale(d.influence_score)}) scale(1)`
        );

      nodeGroup.select('.stakeholder-name-label')
        .transition()
        .duration(200)
        .attr('opacity', 0);
    }
  };

  PowerMap.prototype.updateNameLabels = function() {
    // Get current zoom scale
    var currentTransform = d3.zoomTransform(this.svg.node());
    var scale = currentTransform.k;

    // Show name labels when zoomed in
    this.chartArea.selectAll('.stakeholder-name-label')
      .transition()
      .duration(300)
      .attr('opacity', scale > 1.5 ? 0.8 : 0);
  };

  PowerMap.prototype.getPriorityColor = function(priority) {
    var colors = {
      'high': '#dc3545',
      'medium': '#fd7e14',
      'low': '#6c757d'
    };
    return colors[priority] || colors['low'];
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
      <div style="border-bottom: 1px solid #666; padding-bottom: 8px; margin-bottom: 8px;">
        <strong style="font-size: 14px;">${d.name}</strong><br>
        <em style="color: #ccc;">${d.stakeholder_type || 'Unknown Type'}</em>
      </div>

      <div style="margin-bottom: 8px;">
        <strong>Assessment:</strong><br>
        <span style="color: #ffc107;">⚡</span> <strong>Influence:</strong> ${this.formatInfluenceLevel(d.influence_level)}<br>
        <span style="color: ${this.getSupportIndicatorColor(d.support_score)};">❤</span> <strong>Support:</strong> ${this.formatSupportLevel(d.support_level)}<br>
        <span style="color: #17a2b8;">📍</span> <strong>Quadrant:</strong> ${this.formatQuadrant(d.quadrant)}
      </div>

      <div style="margin-bottom: 8px;">
        <strong>Strategy:</strong><br>
        <span style="font-size: 12px; color: #ccc;">${strategy.strategy}</span><br>
        <span style="color: ${this.getPriorityColor(d.engagement_priority)};">⚡</span> <strong>Priority:</strong> ${this.formatPriority(d.engagement_priority)}
      </div>

      <div style="font-size: 11px; color: #999; border-top: 1px solid #666; padding-top: 6px;">
        <strong>Last Assessed:</strong> ${lastAssessed}<br>
        <span style="opacity: 0.8;">Double-click to open contact record</span>
      </div>
    `;

    this.tooltip
      .style('display', 'block')
      .html(tooltipContent);

    this.moveTooltip(event);
  };

  PowerMap.prototype.formatInfluenceLevel = function(level) {
    var labels = {
      'high': 'High ⭐⭐⭐',
      'medium': 'Medium ⭐⭐',
      'low': 'Low ⭐'
    };
    return labels[level] || level;
  };

  PowerMap.prototype.formatSupportLevel = function(level) {
    var labels = {
      'strong_support': 'Strong Support',
      'support': 'Support',
      'neutral': 'Neutral',
      'opposition': 'Opposition',
      'strong_opposition': 'Strong Opposition'
    };
    return labels[level] || level;
  };

  PowerMap.prototype.formatQuadrant = function(quadrant) {
    var labels = {
      'champions': 'Champions 🏆',
      'targets': 'Targets 🎯',
      'grassroots': 'Grassroots 🌱',
      'monitor': 'Monitor 👀'
    };
    return labels[quadrant] || quadrant;
  };

  PowerMap.prototype.formatPriority = function(priority) {
    var labels = {
      'high': 'High Priority 🔥',
      'medium': 'Medium Priority ⚡',
      'low': 'Low Priority 📋'
    };
    return labels[priority] || priority;
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
    if (y < 0) {
      y = 10;
    }

    this.tooltip
      .style('left', x + 'px')
      .style('top', y + 'px');
  };

  PowerMap.prototype.hideTooltip = function() {
    this.tooltip
      .transition()
      .duration(200)
      .style('opacity', 0)
      .on('end', function() {
        d3.select(this).style('display', 'none').style('opacity', 1);
      });
  };

  PowerMap.prototype.selectStakeholder = function(stakeholder) {
    this.selectedStakeholder = stakeholder;

    // Update visualization
    this.chartArea.selectAll('.stakeholder-node')
      .classed('selected', d => d.id === stakeholder.id);

    // Add selection ring
    this.chartArea.selectAll('.selection-ring').remove();

    var selectedNode = this.chartArea.select(`.stakeholder-node[data-id="${stakeholder.id}"]`);
    selectedNode.insert('circle', '.priority-ring')
      .attr('class', 'selection-ring')
      .attr('r', this.sizeScale(stakeholder.influence_level) + 8)
      .attr('fill', 'none')
      .attr('stroke', '#007bff')
      .attr('stroke-width', 3)
      .attr('stroke-dasharray', '5,5')
      .attr('opacity', 0.8);

    // Animate selection ring
    selectedNode.select('.selection-ring')
      .transition()
      .duration(1500)
      .ease(d3.easeLinear)
      .attrTween('stroke-dashoffset', function() {
        return d3.interpolate(0, 20);
      })
      .on('end', function repeat() {
        d3.select(this)
          .transition()
          .duration(1500)
          .ease(d3.easeLinear)
          .attrTween('stroke-dashoffset', function() {
            return d3.interpolate(0, 20);
          })
          .on('end', repeat);
      });

    // Highlight in sidebar
    this.highlightSidebarItem(stakeholder);

    // Center on selected stakeholder
    this.centerOnStakeholder(stakeholder);
  };

  PowerMap.prototype.centerOnStakeholder = function(stakeholder) {
    var x = this.xScale(stakeholder.support_score) + this.options.margin.left;
    var y = this.yScale(stakeholder.influence_score) + this.options.margin.top;

    var centerX = this.options.width / 2;
    var centerY = this.options.height / 2;

    var transform = d3.zoomIdentity
      .translate(centerX - x, centerY - y)
      .scale(1.5);

    this.svg.transition()
      .duration(750)
      .call(this.zoom.transform, transform);
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
        priority: 'high',
        color: '#28a745'
      },
      'targets': {
        strategy: 'Persuade and convert',
        actions: ['Direct lobbying', 'Relationship building', 'Education'],
        priority: 'high',
        color: '#dc3545'
      },
      'grassroots': {
        strategy: 'Mobilize and amplify',
        actions: ['Volunteer recruitment', 'Social media', 'Testimonials'],
        priority: 'medium',
        color: '#17a2b8'
      },
      'monitor': {
        strategy: 'Monitor but don\'t prioritize',
        actions: ['Information sharing', 'Long-term cultivation'],
        priority: 'low',
        color: '#6c757d'
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

      return b.influence_score - a.influence_score;
    });

    // Group by quadrant
    var groupedStakeholders = _.groupBy(sortedStakeholders, 'quadrant');

    // Define quadrant order and labels
    var quadrantOrder = ['champions', 'targets', 'grassroots', 'monitor'];
    var quadrantLabels = {
      'champions': 'Champions',
      'targets': 'Targets',
      'grassroots': 'Grassroots',
      'monitor': 'Monitor'
    };

    quadrantOrder.forEach(function(quadrant) {
      var stakeholders = groupedStakeholders[quadrant] || [];
      if (stakeholders.length === 0) return;

      // Add quadrant header
      var header = $('<div class="quadrant-header">')
        .html(`<h4>${quadrantLabels[quadrant]} (${stakeholders.length})</h4>`)
        .css({
          'padding': '10px',
          'margin': '10px 0 5px 0',
          'background': self.getEngagementStrategy(quadrant).color + '20',
          'border-left': '4px solid ' + self.getEngagementStrategy(quadrant).color,
          'border-radius': '4px',
          'font-weight': 'bold'
        });

      listContainer.append(header);

      // Add stakeholders in this quadrant
      stakeholders.forEach(function(stakeholder) {
        var strategy = self.getEngagementStrategy(stakeholder.quadrant);

        var item = $('<div class="stakeholder-item">')
          .attr('data-contact-id', stakeholder.id)
          .html(`
            <div class="stakeholder-name">${stakeholder.name}</div>
            <div class="stakeholder-details">
              <span class="influence-badge" style="background: ${self.colorScale(stakeholder.influence_level)};">
                ${stakeholder.influence_level} influence
              </span>
              <span class="support-badge" style="color: ${self.getSupportIndicatorColor(stakeholder.support_score)};">
                ${self.getSupportIndicator(stakeholder.support_score)} ${stakeholder.support_level}
              </span>
            </div>
            <div class="stakeholder-type">
              ${stakeholder.stakeholder_type || 'Unknown Type'}
            </div>
            <div class="engagement-info" style="font-size: 11px; color: #666; margin-top: 5px;">
              ${strategy.strategy}
            </div>
          `)
          .css({
            'cursor': 'pointer',
            'padding': '8px',
            'margin': '2px 0',
            'border-radius': '4px',
            'transition': 'all 0.2s'
          });

        // Add click handler
        item.on('click', function() {
          self.selectStakeholder(stakeholder);
        });

        // Add hover effects
        item.hover(
          function() {
            $(this).css('background-color', '#f8f9fa');
            self.highlightStakeholder(stakeholder, true);
          },
          function() {
            $(this).css('background-color', 'transparent');
            if (!self.selectedStakeholder || self.selectedStakeholder.id !== stakeholder.id) {
              self.highlightStakeholder(stakeholder, false);
            }
          }
        );

        listContainer.append(item);
      });
    });

    // Update sidebar stats
    this.updateSidebarStats();
  };

  PowerMap.prototype.highlightSidebarItem = function(stakeholder) {
    $('#stakeholder-list .stakeholder-item')
      .removeClass('selected')
      .css('background-color', 'transparent');

    var selectedItem = $(`#stakeholder-list .stakeholder-item[data-contact-id="${stakeholder.id}"]`)
      .addClass('selected')
      .css('background-color', '#e3f2fd');

    // Scroll to item in sidebar
    if (selectedItem.length) {
      selectedItem[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  };

  PowerMap.prototype.updateSidebarStats = function() {
    var stats = this.calculateStats();

    // Update any existing stats display in sidebar
    var statsContainer = $('#stakeholder-stats');
    if (statsContainer.length) {
      statsContainer.html(`
        <div class="stat-grid">
          <div class="stat-item">
            <span class="stat-number">${stats.total}</span>
            <span class="stat-label">Total</span>
          </div>
          <div class="stat-item champions">
            <span class="stat-number">${stats.champions}</span>
            <span class="stat-label">Champions</span>
          </div>
          <div class="stat-item targets">
            <span class="stat-number">${stats.targets}</span>
            <span class="stat-label">Targets</span>
          </div>
          <div class="stat-item grassroots">
            <span class="stat-number">${stats.grassroots}</span>
            <span class="stat-label">Grassroots</span>
          </div>
        </div>
      `);
    }
  };

  PowerMap.prototype.updateStats = function() {
    var stats = this.calculateStats();

    // Update dashboard stats
    $('#total-stakeholders').text(stats.total);
    $('#champions-count').text(stats.champions);
    $('#targets-count').text(stats.targets);
    $('#grassroots-count').text(stats.grassroots);
    $('#monitor-count').text(stats.monitor);
  };

  PowerMap.prototype.calculateStats = function() {
    var stats = {
      total: this.filteredStakeholders.length,
      champions: 0,
      targets: 0,
      grassroots: 0,
      monitor: 0
    };

    this.filteredStakeholders.forEach(function(stakeholder) {
      if (stats.hasOwnProperty(stakeholder.quadrant)) {
        stats[stakeholder.quadrant]++;
      }
    });

    return stats;
  };

  PowerMap.prototype.bindEvents = function() {
    var self = this;

    // Handle clicks outside nodes to deselect
    this.svg.on('click', function(event) {
      if (event.target === event.currentTarget || event.target.classList.contains('map-background')) {
        self.deselectAll();
      }
    });

    // Filter controls
    $('#campaign-filter, #type-filter, #quadrant-filter').on('change', function() {
      self.applyFilters();
    });

    // Export functionality
    $('#export-map').on('click', function() {
      self.exportMap();
    });

    // Save view functionality
    $('#save-view').on('click', function() {
      self.saveCurrentView();
    });

    // Full screen toggle
    $('#full-screen').on('click', function() {
      self.toggleFullscreen();
    });

    // Listen for zoom events to update name labels
    this.svg.on('zoom', function() {
      self.updateNameLabels();
    });
  };

  PowerMap.prototype.setupKeyboardHandlers = function() {
    var self = this;

    $(document).on('keydown', function(event) {
      if (!self.selectedStakeholder) return;

      switch(event.key) {
        case 'Delete':
        case 'Backspace':
          if (confirm('Remove this stakeholder from the power map?')) {
            self.removeStakeholder(self.selectedStakeholder);
          }
          break;
        case 'Enter':
          self.openStakeholderRecord(self.selectedStakeholder);
          break;
        case 'Escape':
          self.deselectAll();
          break;
      }
    });
  };

  PowerMap.prototype.initializeSearch = function() {
    var self = this;

    $('#stakeholder-search-input').on('input', function() {
      var searchTerm = $(this).val().toLowerCase();
      self.filterBySearch(searchTerm);
    });
  };

  PowerMap.prototype.filterBySearch = function(searchTerm) {
    if (!searchTerm) {
      this.filteredStakeholders = this.stakeholders.slice();
    } else {
      this.filteredStakeholders = this.stakeholders.filter(function(stakeholder) {
        return stakeholder.name.toLowerCase().includes(searchTerm) ||
          (stakeholder.stakeholder_type && stakeholder.stakeholder_type.toLowerCase().includes(searchTerm)) ||
          (stakeholder.quadrant && stakeholder.quadrant.toLowerCase().includes(searchTerm));
      });
    }

    this.updateVisualization();
    this.updateSidebar();
    this.updateStats();
  };

  PowerMap.prototype.applyFilters = function() {
    var campaignFilter = $('#campaign-filter').val();
    var typeFilter = $('#type-filter').val() || [];
    var quadrantFilter = $('#quadrant-filter').val();
    var searchTerm = $('#stakeholder-search-input').val().toLowerCase();

    this.filteredStakeholders = this.stakeholders.filter(function(stakeholder) {
      // Campaign filter
      if (campaignFilter && stakeholder.campaign_id != campaignFilter) {
        return false;
      }

      // Type filter
      if (typeFilter.length > 0 && !typeFilter.includes(stakeholder.stakeholder_type)) {
        return false;
      }

      // Quadrant filter
      if (quadrantFilter && stakeholder.quadrant !== quadrantFilter) {
        return false;
      }

      // Search filter
      if (searchTerm && !stakeholder.name.toLowerCase().includes(searchTerm) &&
        (!stakeholder.stakeholder_type || !stakeholder.stakeholder_type.toLowerCase().includes(searchTerm))) {
        return false;
      }

      return true;
    });

    this.updateVisualization();
    this.updateSidebar();
    this.updateStats();
  };

  PowerMap.prototype.deselectAll = function() {
    this.selectedStakeholder = null;
    this.chartArea.selectAll('.stakeholder-node').classed('selected', false);
    this.chartArea.selectAll('.selection-ring').remove();
    $('#stakeholder-list .stakeholder-item').removeClass('selected').css('background-color', 'transparent');
  };

  PowerMap.prototype.removeStakeholder = function(stakeholder) {
    var index = this.stakeholders.findIndex(s => s.id === stakeholder.id);
    if (index > -1) {
      this.stakeholders.splice(index, 1);
      this.filteredStakeholders = this.stakeholders.slice();
      this.updateVisualization();
      this.updateSidebar();
      this.updateStats();
      this.deselectAll();
    }
  };

  PowerMap.prototype.exportMap = function() {
    // Create export options dialog
    var exportOptions = `
      <div class="export-dialog">
        <div class="form-group">
          <label for="export-format">Format:</label>
          <select id="export-format">
            <option value="png">PNG Image</option>
            <option value="svg">SVG Vector</option>
            <option value="json">JSON Data</option>
            <option value="csv">CSV Data</option>
            <option value="pdf">PDF Report</option>
          </select>
        </div>
        <div class="form-group">
          <label for="export-options">Include:</label>
          <div id="export-options">
            <label><input type="checkbox" id="include-names" checked> Stakeholder Names</label><br>
            <label><input type="checkbox" id="include-quadrants" checked> Quadrant Labels</label><br>
            <label><input type="checkbox" id="include-legend" checked> Legend</label>
          </div>
        </div>
      </div>
    `;

    CRM.confirm({
      title: 'Export Power Map',
      message: exportOptions,
      options: {
        yes: 'Export',
        no: 'Cancel'
      }
    }).on('crmConfirm:yes', function() {
      var format = $('#export-format').val();
      var includeNames = $('#include-names').is(':checked');
      var includeQuadrants = $('#include-quadrants').is(':checked');
      var includeLegend = $('#include-legend').is(':checked');

      this.performExport(format, {
        includeNames: includeNames,
        includeQuadrants: includeQuadrants,
        includeLegend: includeLegend
      });
    }.bind(this));
  };

  PowerMap.prototype.performExport = function(format, options) {
    switch(format) {
      case 'png':
        this.exportAsPNG(options);
        break;
      case 'svg':
        this.exportAsSVG(options);
        break;
      case 'json':
        this.exportAsJSON();
        break;
      case 'csv':
        this.exportAsCSV();
        break;
      case 'pdf':
        this.exportAsPDF(options);
        break;
    }
  };

  PowerMap.prototype.exportAsPNG = function(options) {
    var svgElement = this.svg.node();
    var svgData = new XMLSerializer().serializeToString(svgElement);

    var canvas = document.createElement('canvas');
    var ctx = canvas.getContext('2d');

    canvas.width = this.options.width;
    canvas.height = this.options.height;

    var img = new Image();
    var svgBlob = new Blob([svgData], {type: 'image/svg+xml;charset=utf-8'});
    var url = URL.createObjectURL(svgBlob);

    img.onload = function() {
      ctx.drawImage(img, 0, 0);
      URL.revokeObjectURL(url);

      canvas.toBlob(function(blob) {
        var link = document.createElement('a');
        link.download = 'power-map.png';
        link.href = URL.createObjectURL(blob);
        link.click();
        URL.revokeObjectURL(link.href);
      });
    };

    img.src = url;
  };

  PowerMap.prototype.exportAsSVG = function(options) {
    var svgElement = this.svg.node().cloneNode(true);
    var svgData = new XMLSerializer().serializeToString(svgElement);

    var blob = new Blob([svgData], {type: 'image/svg+xml;charset=utf-8'});
    var url = URL.createObjectURL(blob);

    var link = document.createElement('a');
    link.download = 'power-map.svg';
    link.href = url;
    link.click();

    URL.revokeObjectURL(url);
  };

  PowerMap.prototype.exportAsJSON = function() {
    var data = {
      stakeholders: this.stakeholders,
      metadata: {
        exportDate: new Date().toISOString(),
        totalStakeholders: this.stakeholders.length,
        stats: this.calculateStats()
      }
    };

    var blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
    var url = URL.createObjectURL(blob);

    var link = document.createElement('a');
    link.download = 'power-map-data.json';
    link.href = url;
    link.click();

    URL.revokeObjectURL(url);
  };

  PowerMap.prototype.exportAsCSV = function() {
    var headers = [
      'Name', 'Influence Level', 'Support Level', 'Quadrant',
      'Stakeholder Type', 'Engagement Priority', 'Strategy'
    ];

    var rows = this.stakeholders.map(function(stakeholder) {
      var strategy = this.getEngagementStrategy(stakeholder.quadrant);
      return [
        stakeholder.name,
        stakeholder.influence_level,
        stakeholder.support_level,
        stakeholder.quadrant,
        stakeholder.stakeholder_type || '',
        stakeholder.engagement_priority || '',
        strategy.strategy
      ];
    }.bind(this));

    var csvContent = [headers.join(',')]
      .concat(rows.map(row => row.map(cell => `"${cell}"`).join(',')))
      .join('\n');

    var blob = new Blob([csvContent], {type: 'text/csv;charset=utf-8'});
    var url = URL.createObjectURL(blob);

    var link = document.createElement('a');
    link.download = 'power-map-data.csv';
    link.href = url;
    link.click();

    URL.revokeObjectURL(url);
  };

  PowerMap.prototype.exportAsPDF = function(options) {
    // This would require a PDF library like jsPDF
    CRM.alert('PDF export requires additional setup. Please use PNG or SVG export instead.', 'Feature Not Available', 'info');
  };

  PowerMap.prototype.saveCurrentView = function() {
    var currentTransform = d3.zoomTransform(this.svg.node());
    var viewState = {
      transform: {
        x: currentTransform.x,
        y: currentTransform.y,
        k: currentTransform.k
      },
      filters: {
        campaign: $('#campaign-filter').val(),
        type: $('#type-filter').val(),
        quadrant: $('#quadrant-filter').val(),
        search: $('#stakeholder-search-input').val()
      },
      selectedStakeholder: this.selectedStakeholder ? this.selectedStakeholder.id : null
    };

    localStorage.setItem('powermap_view_state', JSON.stringify(viewState));
    CRM.alert('Current view saved successfully!', 'View Saved', 'success', {expires: 2000});
  };

  PowerMap.prototype.loadSavedView = function() {
    var savedState = localStorage.getItem('powermap_view_state');
    if (!savedState) return;

    try {
      var viewState = JSON.parse(savedState);

      // Restore filters
      if (viewState.filters) {
        $('#campaign-filter').val(viewState.filters.campaign || '').trigger('change');
        $('#type-filter').val(viewState.filters.type || []).trigger('change');
        $('#quadrant-filter').val(viewState.filters.quadrant || '').trigger('change');
        $('#stakeholder-search-input').val(viewState.filters.search || '');
      }

      // Restore transform
      if (viewState.transform) {
        var transform = d3.zoomIdentity
          .translate(viewState.transform.x, viewState.transform.y)
          .scale(viewState.transform.k);

        this.svg.call(this.zoom.transform, transform);
      }

      // Restore selection
      if (viewState.selectedStakeholder) {
        var stakeholder = this.stakeholders.find(s => s.id === viewState.selectedStakeholder);
        if (stakeholder) {
          setTimeout(() => this.selectStakeholder(stakeholder), 500);
        }
      }
    } catch (error) {
      console.warn('Failed to load saved view state:', error);
    }
  };

  PowerMap.prototype.toggleFullscreen = function() {
    var container = $(this.container).closest('.crm-powermap-main')[0];

    if (!document.fullscreenElement) {
      container.requestFullscreen().then(() => {
        $('#full-screen').text('Exit Full Screen');
        this.handleResize();
      });
    } else {
      document.exitFullscreen().then(() => {
        $('#full-screen').text('Full Screen');
        this.handleResize();
      });
    }
  };

  PowerMap.prototype.handleResize = function() {
    var container = $(this.container);
    var newWidth = container.width();
    var newHeight = container.height();

    if (newWidth !== this.options.width || newHeight !== this.options.height) {
      this.options.width = newWidth;
      this.options.height = newHeight;

      this.svg
        .attr('width', newWidth)
        .attr('height', newHeight);

      this.chartWidth = newWidth - this.options.margin.left - this.options.margin.right;
      this.chartHeight = newHeight - this.options.margin.top - this.options.margin.bottom;

      this.createScales();
      this.updateVisualization();
    }
  };

  // Public API methods
  PowerMap.prototype.addStakeholder = function(stakeholderData) {
    // Add new stakeholder to the map
    var stakeholder = Object.assign({
      id: Date.now(), // temporary ID
      influence_level: 'medium',
      support_level: 'neutral',
      engagement_priority: 'medium'
    }, stakeholderData);

    this.stakeholders.push(stakeholder);
    this.processStakeholderData();
    this.applyFilters(); // This will update visualization
  };

  PowerMap.prototype.updateStakeholder = function(stakeholderId, updates) {
    var stakeholder = this.stakeholders.find(s => s.id === stakeholderId);
    if (stakeholder) {
      Object.assign(stakeholder, updates);
      this.processStakeholderData();
      this.updateVisualization();
      this.updateSidebar();
    }
  };

  PowerMap.prototype.getStakeholders = function() {
    return this.stakeholders.slice(); // Return copy
  };

  PowerMap.prototype.clearSelection = function() {
    this.deselectAll();
  };

  PowerMap.prototype.focusOnQuadrant = function(quadrant) {
    // Filter to show only stakeholders in specified quadrant
    $('#quadrant-filter').val(quadrant).trigger('change');

    // Center view on quadrant
    var quadrantCenters = {
      'champions': { x: this.chartWidth * 0.75, y: this.chartHeight * 0.25 },
      'targets': { x: this.chartWidth * 0.25, y: this.chartHeight * 0.25 },
      'grassroots': { x: this.chartWidth * 0.75, y: this.chartHeight * 0.75 },
      'monitor': { x: this.chartWidth * 0.25, y: this.chartHeight * 0.75 }
    };

    var center = quadrantCenters[quadrant];
    if (center) {
      var transform = d3.zoomIdentity
        .translate(
          this.options.width / 2 - center.x - this.options.margin.left,
          this.options.height / 2 - center.y - this.options.margin.top
        )
        .scale(1.5);

      this.svg.transition()
        .duration(750)
        .call(this.zoom.transform, transform);
    }
  };

  PowerMap.prototype.destroy = function() {
    // Clean up event listeners and DOM elements
    $(document).off('keydown');
    $('#stakeholder-search-input').off('input');
    $('#campaign-filter, #type-filter, #quadrant-filter').off('change');

    this.tooltip.remove();
    d3.select(this.container).selectAll('*').remove();
  };

  // Initialize PowerMap when document is ready
  $(document).ready(function() {
    // Auto-initialize power map if container exists
    if ($('#power-map-visualization').length) {
      window.powerMapInstance = new PowerMap('#power-map-visualization', {
        width: $('#power-map-visualization').width() || 800,
        height: $('#power-map-visualization').height() || 600
      });
    }

    // Handle window resize
    $(window).on('resize', function() {
      if (window.powerMapInstance) {
        clearTimeout(window.powerMapInstance.resizeTimeout);
        window.powerMapInstance.resizeTimeout = setTimeout(function() {
          window.powerMapInstance.handleResize();
        }, 250);
      }
    });

    // Initialize Select2 dropdowns
    $('.crm-select2').select2({
      width: '100%',
      placeholder: function() {
        return $(this).attr('placeholder') || $(this).find('option:first').text();
      },
      allowClear: true
    });
  });

  // Export PowerMap class to global scope
  window.PowerMap = PowerMap;

})(CRM.$, CRM._, d3);.5);

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
  .attr('stroke-dasharray', '2,2')
  .attr('opacity', 0.5);

// Main axes
// X-axis (support level)
this.chartArea.append('line')
  .attr('class', 'axis axis-x')
  .attr('x1', 0)
  .attr('x2', this.chartWidth)
  .attr('y1', this.yScale(0))
  .attr('y2', this.yScale(0))
  .attr('stroke', '#333')
  .attr('stroke-width', 3)
  .attr('opacity', 0.8);

// Y-axis (influence level)
this.chartArea.append('line')
  .attr('class', 'axis axis-y')
  .attr('x1', this.xScale(0))
  .attr('x2', this.xScale(0))
  .attr('y1', 0)
  .attr('y2', this.chartHeight)
  .attr('stroke', '#333')
  .attr('stroke-width', 3)
  .attr('opacity', 0.8);

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
    { value: -2, label: 'Strong\nOpposition' },
    { value: -1, label: 'Opposition' },
    { value: 0, label: 'Neutral' },
    { value: 1, label: 'Support' },
    { value: 2, label: 'Strong\nSupport' }
  ];

  supportLabels.forEach(item => {
    var textElement = this.svg.append('text')
      .attr('x', this.options.margin.left + this.xScale(item.value))
      .attr('y', this.options.height - 25)
      .attr('text-anchor', 'middle')
      .attr('font-size', '10px')
      .attr('fill', '#666')
      .attr('opacity', 0.8);

    item.label.split('\n').forEach((line, i) => {
      textElement.append('tspan')
        .attr('x', this.options.margin.left + this.xScale(item.value))
        .attr('dy', i === 0 ? 0 : '1.2em')
        .text(line);
    });
  });

  // Influence scale indicators
  var influenceLabels = [
    { value: 1, label: 'Low' },
    { value: 2, label: 'Medium' },
    { value: 3, label: 'High' }
  ];

  influenceLabels.forEach(item => {
    this.svg.append('text')
      .attr('x', 25)
      .attr('y', this.options.margin.top + this.yScale(item.value))
      .attr('text-anchor', 'middle')
      .attr('font-size', '10px')
      .attr('fill', '#666')
      .attr('dominant-baseline', 'middle')
      .attr('opacity', 0.8)
      .text(item.label);
  });
};

PowerMap.prototype.drawQuadrantLabels = function() {
  var quadrants = [
    {
      x: this.chartWidth * 0.75,
      y: this.chartHeight * 0.25,
      lines: ['Champions', '(High Influence +', 'High Support)'],
      class: 'champions',
      color: '#155724'
    },
    {
      x: this.chartWidth * 0.25,
      y: this.chartHeight * 0.25,
      lines: ['Targets', '(High Influence +', 'Low Support)'],
      class: 'targets',
      color: '#721c24'
    },
    {
      x: this.chartWidth * 0.25,
      y: this.chartHeight * 0.75,
      lines: ['Monitor', '(Low Influence +', 'Low Support)'],
      class: 'monitor',
      color: '#383d41'
    },
    {
      x: this.chartWidth * 0.75,
      y: this.chartHeight * 0.75,
      lines: ['Grassroots', '(Low Influence +', 'High Support)'],
      class: 'grassroots',
      color: '#0c5460'
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
      .attr('fill', quadrant.color)
      .attr('opacity', 0.7);

    quadrant.lines.forEach((line, i) => {
      textElement.append('tspan')
        .attr('x', 0)
        .attr('dy', i === 0 ? 0 : '1.2em')
        .text(line);
    });

    // Add background rectangle for better readability
    var bbox = textElement.node().getBBox();
    labelGroup.insert('rect', 'text')
      .attr('x', bbox.x - 6)
      .attr('y', bbox.y - 3)
      .attr('width', bbox.width + 12)
      .attr('height', bbox.height + 6)
      .attr('fill', 'rgba(255, 255, 255, 0.9)')
      .attr('rx', 6)
      .attr('stroke', quadrant.color)
      .attr('stroke-width', 1)
      .attr('opacity', 0.8);
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

PowerMap.prototype.setupZoomBehavior = function() {
  var self = this;

  this.zoom = d3.zoom()
    .scaleExtent([0.5, 3])
    .on('zoom', function(event) {
      self.mainGroup.attr('transform', event.transform);
    });

  this.svg.call(this.zoom);

  // Add zoom controls
  this.addZoomControls();
};

PowerMap.prototype.addZoomControls = function() {
  var self = this;
  var controlsGroup = this.svg.append('g')
    .attr('class', 'zoom-controls')
    .attr('transform', 'translate(10, 10)');

  // Zoom in button
  var zoomInBtn = controlsGroup.append('g')
    .attr('class', 'zoom-btn zoom-in')
    .style('cursor', 'pointer');

  zoomInBtn.append('rect')
    .attr('width', 30)
    .attr('height', 30)
    .attr('rx', 4)
    .attr('fill', 'rgba(255, 255, 255, 0.9)')
    .attr('stroke', '#ccc')
    .attr('stroke-width', 1);

  zoomInBtn.append('text')
    .attr('x', 15)
    .attr('y', 20)
    .attr('text-anchor', 'middle')
    .attr('font-size', '16px')
    .attr('font-weight', 'bold')
    .attr('fill', '#333')
    .text('+');

  zoomInBtn.on('click', function() {
    self.svg.transition().duration(300).call(
      self.zoom.scaleBy, 1.5
    );
  });

  // Zoom out button
  var zoomOutBtn = controlsGroup.append('g')
    .attr('class', 'zoom-btn zoom-out')
    .attr('transform', 'translate(0, 35)')
    .style('cursor', 'pointer');

  zoomOutBtn.append('rect')
    .attr('width', 30)
    .attr('height', 30)
    .attr('rx', 4)
    .attr('fill', 'rgba(255, 255, 255, 0.9)')
    .attr('stroke', '#ccc')
    .attr('stroke-width', 1);

  zoomOutBtn.append('text')
    .attr('x', 15)
    .attr('y', 20)
    .attr('text-anchor', 'middle')
    .attr('font-size', '16px')
    .attr('font-weight', 'bold')
    .attr('fill', '#333')
    .text('−');

  zoomOutBtn.on('click', function() {
    self.svg.transition().duration(300).call(
      self.zoom.scaleBy, 0.67
    );
  });

  // Reset zoom button
  var resetBtn = controlsGroup.append('g')
    .attr('class', 'zoom-btn zoom-reset')
    .attr('transform', 'translate(0, 70)')
    .style('cursor', 'pointer');

  resetBtn.append('rect')
    .attr('width', 30)
    .attr('height', 30)
    .attr('rx', 4)
    .attr('fill', 'rgba(255, 255, 255, 0.9)')
    .attr('stroke', '#ccc')
    .attr('stroke-width', 1);

  resetBtn.append('text')
    .attr('x', 15)
    .attr('y', 20)
    .attr('text-anchor', 'middle')
    .attr('font-size', '12px')
    .attr('font-weight', 'bold')
    .attr('fill', '#333')
    .text('⌂');

  resetBtn.on('click', function() {
    self.svg.transition().duration(500).call(
      self.zoom.transform,
      d3.zoomIdentity
    );
  });
};

PowerMap.prototype.dragStarted = function(event, d) {
  d3.select(event.sourceEvent.target.parentNode)
    .classed('dragging', true)
    .transition()
    .duration(100)
    .attr('transform',
      `translate(${event.x}, ${event.y}) scale(1.2)`
    );

  this.hideTooltip();
};

PowerMap.prototype.dragged = function(event, d) {
  var x = Math.max(0, Math.min(this.chartWidth, event.x));
  var y = Math.max(0, Math.min(this.chartHeight, event.y));

  d3.select(event.sourceEvent.target.parentNode)
    .attr('transform', `translate(${x}, ${y}) scale(1.2)`);
};

PowerMap.prototype.dragEnded = function(event, d) {
  var x = Math.max(0, Math.min(this.chartWidth, event.x));
  var y = Math.max(0, Math.min(this.chartHeight, event.y));

  // Convert back to data coordinates
  var supportScore = this.xScale.invert(x);
  var influenceScore = this.yScale.invert(y);

  // Clamp to valid ranges
  supportScore = Math.max(-2, Math.min(2, supportScore));
  influenceScore = Math.max(0.5, Math.min(3, influenceScore));

  // Update stakeholder data
  d.support_score = Math.round(supportScore * 4) / 4; // Round to nearest 0.25
  d.influence_score = Math.round(influenceScore * 4) / 4;
  d.quadrant = this.calculateQuadrant(d.influence_score, d.support_score);

  // Update position with animation
  d3.select(event.sourceEvent.target.parentNode)
    .classed('dragging', false)
    .transition()
    .duration(300)
    .ease(d3.easeCubicOut)
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
  var self = this;

  // Show saving indicator
  this.showSavingIndicator(stakeholder);

  // Save updated position via API
  CRM.api3('Contact', 'create', {
    id: stakeholder.id,
    'custom_' + this.getCustomFieldName('influence_level'): this.getInfluenceLevelFromScore(stakeholder.influence_score),
  'custom_' + this.getCustomFieldName('support_level'): this.getSupportLevelFromScore(stakeholder.support_score),
  'custom_' + this.getCustomFieldName('last_assessment_date'): new Date().toISOString().split('T')[0]
}).done(function(result) {
    self.hideSavingIndicator(stakeholder);
    CRM.alert('Position updated for ' + stakeholder.name, 'Success', 'success', {expires: 3000});
  }).fail(function(error) {
    self.hideSavingIndicator(stakeholder);
    console.error('Failed to save position:', error);
    CRM.alert('Failed to save position changes', 'Error', 'error');
  });
};

PowerMap.prototype.showSavingIndicator = function(stakeholder) {
  var nodeGroup = this.chartArea.select(`.stakeholder-node[data-id="${stakeholder.id}"]`);

  nodeGroup.append('circle')
    .attr('class', 'saving-indicator')
    .attr('r', 35)
    .attr('fill', 'none')
    .attr('stroke', '#007bff')
    .attr('stroke-width', 2)
    .attr('stroke-dasharray', '5,5')
    .attr('opacity', 0.7);

  // Animate the saving indicator
  nodeGroup.select('.saving-indicator')
    .transition()
    .duration(1000)
    .ease(d3.easeLinear)
    .attrTween('stroke-dashoffset', function() {
      return d3.interpolate(0, 20);
    })
    .on('end', function repeat() {
      d3.select(this)
        .transition()
        .duration(1000)
        .ease(d3.easeLinear)
        .attrTween('stroke-dashoffset', function() {
          return d3.interpolate(0, 20);
        })
        .on('end', repeat);
    });
};

PowerMap.prototype.hideSavingIndicator = function(stakeholder) {
  this.chartArea.select(`.stakeholder-node[data-id="${stakeholder.id}"] .saving-indicator`)
    .transition()
    .duration(300)
    .attr('opacity', 0)
    .remove();
};

PowerMap.prototype.getCustomFieldName = function(fieldName) {
  // This would typically come from configuration
  var fieldMap = {
    'influence_level': 'influence_level_1',
    'support_level': 'support_level_2',
    'last_assessment_date': 'last_assessment_date_3'
  };
  return fieldMap[fieldName] || fieldName;
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

    // Load sample data for demonstration
    self.loadSampleData();
  });
};

PowerMap.prototype.loadSampleData = function() {
  // Sample data for demonstration purposes
  this.stakeholders = [
    {
      id: 1,
      name: 'Senator Johnson',
      influence_level: 'high',
      support_level: 'support',
      stakeholder_type: 'politician',
      engagement_priority: 'high'
    },
    {
      id: 2,
      name: 'Mary Chen',
      influence_level: 'medium',
      support_level: 'strong_support',
      stakeholder_type: 'community_leader',
      engagement_priority: 'medium'
    }
  ];

  this.filteredStakeholders = this.stakeholders.slice();
  this.processStakeholderData();
  this.updateVisualization();
  this.updateSidebar();
  this.updateStats();
};

PowerMap.prototype.processStakeholderData = function() {
  var self = this;

  // Ensure all stakeholders have required properties
  this.stakeholders.forEach(function(stakeholder) {
    // Convert string values to numeric scores
    stakeholder.influence_score = self.getNumericInfluenceScore(stakeholder.influence_level);
    stakeholder.support_score = self.getNumericSupportScore(stakeholder.support_level);

    // Calculate quadrant
    stakeholder.quadrant = self.calculateQuadrant(stakeholder.influence_score, stakeholder.support_score);

    // Get engagement strategy
    stakeholder.strategy = self.getEngagementStrategy(stakeholder.quadrant);

    // Generate initials for visualization
    stakeholder.initials = self.getInitials(stakeholder.name);

    // Add small random jitter to avoid overlapping (within 0.1 units)
    stakeholder.support_score += (Math.random() - 0.5) * 0.2;
    stakeholder.influence_score += (Math.random() - 0.5) * 0.2;

    // Ensure values stay within bounds
    stakeholder.support_score = Math.max(-2, Math.min(2, stakeholder.support_score));
    stakeholder.influence_score = Math.max(0.5, Math.min(3, stakeholder.influence_score));
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
  var loadingGroup = this.chartArea.append('g')
    .attr('class', 'loading-indicator');

  // Loading spinner
  var spinner = loadingGroup.append('circle')
    .attr('cx', this.chartWidth / 2)
    .attr('cy', this.chartHeight / 2)
    .attr('r', 20)
    .attr('fill', 'none')
    .attr('stroke', '#007bff')
    .attr('stroke-width', 3)
    .attr('stroke-dasharray', '20,5')
    .attr('opacity', 0
