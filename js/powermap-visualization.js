class PowerMapVisualization {
  constructor(containerId, data) {
    this.containerId = containerId;
    this.originalData = data;
    this.filteredData = { ...data };
    this.width = 800;
    this.height = 600;
    this.simulation = null;
    this.svg = null;
    this.tooltip = null;
    this.currentTransform = d3.zoomIdentity;

    this.colors = {
      high: '#ff4757',
      medium: '#ffa502',
      low: '#2ed573'
    };

    this.init();
  }

  init() {
    this.setupSVG();
    this.setupTooltip();
    this.setupSimulation();
    this.setupZoom();
    this.render();
    this.setupKeyboardShortcuts();
  }

  setupSVG() {
    const container = d3.select(`#${this.containerId}`);
    this.width = container.node().getBoundingClientRect().width || 800;
    this.height = container.node().getBoundingClientRect().height || 600;

    this.svg = container
      .append('svg')
      .attr('width', this.width)
      .attr('height', this.height)
      .attr('class', 'powermap-svg');

    // Create groups for different elements
    this.linksGroup = this.svg.append('g').attr('class', 'links');
    this.nodesGroup = this.svg.append('g').attr('class', 'nodes');
    this.labelsGroup = this.svg.append('g').attr('class', 'labels');
  }

  setupTooltip() {
    this.tooltip = d3.select('body')
      .append('div')
      .attr('class', 'powermap-tooltip')
      .style('opacity', 0);
  }

  setupSimulation() {
    this.simulation = d3.forceSimulation()
      .force('link', d3.forceLink().id(d => d.id).distance(80).strength(0.1))
      .force('charge', d3.forceManyBody().strength(-300))
      .force('center', d3.forceCenter(this.width / 2, this.height / 2))
      .force('collision', d3.forceCollide().radius(d => this.getNodeRadius(d) + 5));
  }

  setupZoom() {
    const zoom = d3.zoom()
      .scaleExtent([0.1, 4])
      .on('zoom', (event) => {
        this.currentTransform = event.transform;
        this.svg.selectAll('.links, .nodes, .labels')
          .attr('transform', event.transform);
      });

    this.svg.call(zoom);
  }

  setupKeyboardShortcuts() {
    d3.select('body').on('keydown', (event) => {
      if (event.ctrlKey || event.metaKey) {
        switch(event.key) {
          case '=':
          case '+':
            event.preventDefault();
            this.zoomIn();
            break;
          case '-':
            event.preventDefault();
            this.zoomOut();
            break;
          case '0':
            event.preventDefault();
            this.resetZoom();
            break;
        }
      }
    });
  }

  render() {
    this.renderLinks();
    this.renderNodes();
    this.renderLabels();
    this.updateSimulation();
    this.updateStats();
  }

  renderLinks() {
    const links = this.linksGroup
      .selectAll('.link')
      .data(this.filteredData.links, d => `${d.source.id || d.source}-${d.target.id || d.target}`);

    links.exit().remove();

    const linksEnter = links.enter()
      .append('line')
      .attr('class', 'link')
      .attr('stroke', '#999')
      .attr('stroke-opacity', 0.6)
      .attr('stroke-width', d => this.getLinkWidth(d))
      .attr('marker-end', 'url(#arrowhead)');

    // Add arrow markers
    this.svg.append('defs').selectAll('marker')
      .data(['arrowhead'])
      .enter().append('marker')
      .attr('id', 'arrowhead')
      .attr('viewBox', '-0 -5 10 10')
      .attr('refX', 20)
      .attr('refY', 0)
      .attr('orient', 'auto')
      .attr('markerWidth', 8)
      .attr('markerHeight', 8)
      .attr('xoverflow', 'visible')
      .append('svg:path')
      .attr('d', 'M 0,-5 L 10 ,0 L 0,5')
      .attr('fill', '#999')
      .style('stroke', 'none');

    this.links = linksEnter.merge(links);
  }

  renderNodes() {
    const nodes = this.nodesGroup
      .selectAll('.node')
      .data(this.filteredData.nodes, d => d.id);

    nodes.exit().remove();

    const nodesEnter = nodes.enter()
      .append('circle')
      .attr('class', 'node')
      .attr('r', d => this.getNodeRadius(d))
      .attr('fill', d => this.getNodeColor(d))
      .attr('stroke', '#fff')
      .attr('stroke-width', 2)
      .style('cursor', 'pointer')
      .call(this.getDragHandler());

    // Add hover and click events
    nodesEnter
      .on('mouseover', (event, d) => this.showTooltip(event, d))
      .on('mouseout', () => this.hideTooltip())
      .on('click', (event, d) => this.highlightConnections(d));

    this.nodes = nodesEnter.merge(nodes);
  }

  renderLabels() {
    const labels = this.labelsGroup
      .selectAll('.label')
      .data(this.filteredData.nodes, d => d.id);

    labels.exit().remove();

    const labelsEnter = labels.enter()
      .append('text')
      .attr('class', 'label')
      .attr('text-anchor', 'middle')
      .attr('dy', d => this.getNodeRadius(d) + 15)
      .style('font-size', '12px')
      .style('fill', '#333')
      .style('pointer-events', 'none')
      .text(d => this.truncateText(d.name, 15));

    this.labels = labelsEnter.merge(labels);
  }

  updateSimulation() {
    this.simulation
      .nodes(this.filteredData.nodes)
      .on('tick', () => this.tick());

    this.simulation.force('link')
      .links(this.filteredData.links);

    this.simulation.alpha(1).restart();
  }

  tick() {
    this.links
      .attr('x1', d => d.source.x)
      .attr('y1', d => d.source.y)
      .attr('x2', d => d.target.x)
      .attr('y2', d => d.target.y);

    this.nodes
      .attr('cx', d => d.x)
      .attr('cy', d => d.y);

    this.labels
      .attr('x', d => d.x)
      .attr('y', d => d.y);
  }

  getDragHandler() {
    return d3.drag()
      .on('start', (event, d) => {
        if (!event.active) this.simulation.alphaTarget(0.3).restart();
        d.fx = d.x;
        d.fy = d.y;
      })
      .on('drag', (event, d) => {
        d.fx = event.x;
        d.fy = event.y;
      })
      .on('end', (event, d) => {
        if (!event.active) this.simulation.alphaTarget(0);
        d.fx = null;
        d.fy = null;
      });
  }

  getNodeRadius(d) {
    return 5 + (d.influence * 3);
  }

  getNodeColor(d) {
    if (d.influence >= 4) return this.colors.high;
    if (d.influence >= 3) return this.colors.medium;
    return this.colors.low;
  }

  getLinkWidth(d) {
    return d.strength || 2;
  }

  showTooltip(event, d) {
    const connections = this.getConnections(d);
    this.tooltip.transition()
      .duration(200)
      .style('opacity', 0.9);

    this.tooltip.html(`
      <div class="tooltip-content">
        <h4>${d.name}</h4>
        <p><strong>Type:</strong> ${d.type}</p>
        <p><strong>Influence:</strong> ${d.influence}/5</p>
        <p><strong>Support:</strong> ${d.support}/5</p>
        <p><strong>Connections:</strong> ${connections.length}</p>
      </div>
    `)
      .style('left', (event.pageX + 10) + 'px')
      .style('top', (event.pageY - 28) + 'px');
  }

  hideTooltip() {
    this.tooltip.transition()
      .duration(500)
      .style('opacity', 0);
  }

  highlightConnections(node) {
    const connections = this.getConnections(node);
    const connectedIds = new Set([node.id, ...connections.map(c => c.id)]);

    // Reset all nodes and links
    this.nodes
      .style('opacity', d => connectedIds.has(d.id) ? 1 : 0.3)
      .attr('stroke-width', d => d.id === node.id ? 4 : 2);

    this.links
      .style('opacity', d =>
        (d.source.id === node.id || d.target.id === node.id) ? 1 : 0.1)
      .attr('stroke-width', d =>
        (d.source.id === node.id || d.target.id === node.id) ? 3 : 1);

    this.labels
      .style('opacity', d => connectedIds.has(d.id) ? 1 : 0.3);
  }

  getConnections(node) {
    return this.filteredData.nodes.filter(n => {
      return this.filteredData.links.some(link =>
        (link.source.id === node.id && link.target.id === n.id) ||
        (link.target.id === node.id && link.source.id === n.id)
      );
    });
  }

  truncateText(text, maxLength) {
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
  }

  // Filter methods
  applyFilters(filters) {
    const {
      influenceMin = 1,
      supportMin = 1,
      relationshipTypes = [],
      searchTerm = ''
    } = filters;

    // Filter nodes
    this.filteredData.nodes = this.originalData.nodes.filter(node => {
      const influenceMatch = node.influence >= influenceMin;
      const supportMatch = node.support >= supportMin;
      const searchMatch = searchTerm === '' ||
        node.name.toLowerCase().includes(searchTerm.toLowerCase());

      return influenceMatch && supportMatch && searchMatch;
    });

    // Get filtered node IDs
    const nodeIds = new Set(this.filteredData.nodes.map(n => n.id));

    // Filter links
    this.filteredData.links = this.originalData.links.filter(link => {
      const sourceExists = nodeIds.has(link.source.id || link.source);
      const targetExists = nodeIds.has(link.target.id || link.target);
      const typeMatch = relationshipTypes.length === 0 ||
        relationshipTypes.includes(link.type);

      return sourceExists && targetExists && typeMatch;
    });

    this.render();
  }

  // Zoom methods
  zoomIn() {
    this.svg.transition().duration(300).call(
      d3.zoom().transform,
      this.currentTransform.scale(1.5)
    );
  }

  zoomOut() {
    this.svg.transition().duration(300).call(
      d3.zoom().transform,
      this.currentTransform.scale(0.75)
    );
  }

  resetZoom() {
    this.svg.transition().duration(500).call(
      d3.zoom().transform,
      d3.zoomIdentity
    );
  }

  centerView() {
    const bounds = this.nodesGroup.node().getBBox();
    const fullWidth = this.width;
    const fullHeight = this.height;
    const width = bounds.width;
    const height = bounds.height;
    const midX = bounds.x + width / 2;
    const midY = bounds.y + height / 2;

    if (width == 0 || height == 0) return;

    const scale = Math.min(fullWidth / width, fullHeight / height) * 0.9;
    const translate = [fullWidth / 2 - scale * midX, fullHeight / 2 - scale * midY];

    this.svg.transition().duration(750).call(
      d3.zoom().transform,
      d3.zoomIdentity.translate(translate[0], translate[1]).scale(scale)
    );
  }

  // Stats update
  updateStats() {
    const stats = this.calculateStats();
    this.updateStatsDisplay(stats);
  }

  calculateStats() {
    const total = this.filteredData.nodes.length;
    const highInfluence = this.filteredData.nodes.filter(n => n.influence >= 4).length;
    const supporters = this.filteredData.nodes.filter(n => n.support >= 4).length;
    const opposition = this.filteredData.nodes.filter(n => n.support <= 2).length;

    return { total, highInfluence, supporters, opposition };
  }

  updateStatsDisplay(stats) {
    document.getElementById('stat-total').textContent = stats.total;
    document.getElementById('stat-influence').textContent = stats.highInfluence;
    document.getElementById('stat-supporters').textContent = stats.supporters;
    document.getElementById('stat-opposition').textContent = stats.opposition;
  }

  // Export functionality
  exportToCSV() {
    const csvContent = this.generateCSVContent();
    this.downloadCSV(csvContent, 'powermap-export.csv');
  }

  generateCSVContent() {
    const headers = ['Name', 'Type', 'Influence', 'Support', 'Connections'];
    const rows = [headers];

    this.filteredData.nodes.forEach(node => {
      const connections = this.getConnections(node).length;
      rows.push([
        node.name,
        node.type,
        node.influence,
        node.support,
        connections
      ]);
    });

    return rows.map(row => row.join(',')).join('\n');
  }

  downloadCSV(content, filename) {
    const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');

    if (link.download !== undefined) {
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', filename);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  }

  // Search functionality
  searchStakeholder(searchTerm) {
    const matchingNodes = this.filteredData.nodes.filter(node =>
      node.name.toLowerCase().includes(searchTerm.toLowerCase())
    );

    if (matchingNodes.length > 0) {
      // Highlight matching nodes
      this.nodes
        .style('opacity', d => matchingNodes.some(m => m.id === d.id) ? 1 : 0.3)
        .attr('stroke-width', d => matchingNodes.some(m => m.id === d.id) ? 4 : 2);

      // Center on first match
      const firstMatch = matchingNodes[0];
      if (firstMatch.x && firstMatch.y) {
        const scale = this.currentTransform.k;
        const translate = [
          this.width / 2 - scale * firstMatch.x,
          this.height / 2 - scale * firstMatch.y
        ];

        this.svg.transition().duration(500).call(
          d3.zoom().transform,
          d3.zoomIdentity.translate(translate[0], translate[1]).scale(scale)
        );
      }
    }
  }

  clearSearch() {
    this.nodes
      .style('opacity', 1)
      .attr('stroke-width', 2);
    this.links
      .style('opacity', 0.6);
    this.labels
      .style('opacity', 1);
  }

  // Resize handling
  resize() {
    const container = d3.select(`#${this.containerId}`);
    this.width = container.node().getBoundingClientRect().width || 800;
    this.height = container.node().getBoundingClientRect().height || 600;

    this.svg
      .attr('width', this.width)
      .attr('height', this.height);

    this.simulation
      .force('center', d3.forceCenter(this.width / 2, this.height / 2))
      .alpha(0.3)
      .restart();
  }
}

// PowerMap Controller
class PowerMapController {
  constructor() {
    this.visualization = null;
    this.filters = {
      influenceMin: 1,
      supportMin: 1,
      relationshipTypes: [],
      searchTerm: ''
    };

    this.init();
  }

  init() {
    this.setupEventListeners();
    this.loadData();
  }

  setupEventListeners() {
    // Filter controls
    document.getElementById('influence-slider')?.addEventListener('input', (e) => {
      this.filters.influenceMin = parseInt(e.target.value);
      document.getElementById('influence-value').textContent = e.target.value;
      this.applyFilters();
    });

    document.getElementById('support-slider')?.addEventListener('input', (e) => {
      this.filters.supportMin = parseInt(e.target.value);
      document.getElementById('support-value').textContent = e.target.value;
      this.applyFilters();
    });

    // Search
    document.getElementById('search-input')?.addEventListener('input', (e) => {
      this.filters.searchTerm = e.target.value;
      if (e.target.value === '') {
        this.visualization?.clearSearch();
      } else {
        this.visualization?.searchStakeholder(e.target.value);
      }
    });

    // Control buttons
    document.getElementById('zoom-in')?.addEventListener('click', () => {
      this.visualization?.zoomIn();
    });

    document.getElementById('zoom-out')?.addEventListener('click', () => {
      this.visualization?.zoomOut();
    });

    document.getElementById('reset-view')?.addEventListener('click', () => {
      this.visualization?.resetZoom();
    });

    document.getElementById('center-view')?.addEventListener('click', () => {
      this.visualization?.centerView();
    });

    document.getElementById('export-csv')?.addEventListener('click', () => {
      this.visualization?.exportToCSV();
    });

    // Relationship type checkboxes
    document.querySelectorAll('.relationship-checkbox').forEach(checkbox => {
      checkbox.addEventListener('change', () => {
        this.updateRelationshipTypeFilter();
      });
    });

    // Window resize
    window.addEventListener('resize', () => {
      this.visualization?.resize();
    });
  }

  updateRelationshipTypeFilter() {
    this.filters.relationshipTypes = Array.from(
        document.querySelectorAll('.relationship-checkbox:checked')
    ).map(cb => cb.value);

    this.applyFilters();
  }

  applyFilters() {
    this.visualization?.applyFilters(this.filters);
  }

  loadData() {
    // In a real implementation, this would fetch from CiviCRM API
    // For now, use the data passed from PHP
    if (false && typeof window.powermapData !== 'undefined') {
      this.initializeVisualization(window.powermapData);
    } else {
      // Fallback to demo data
      this.loadDemoData();
    }
  }

  loadDemoData() {
    const demoData = {
      nodes: [
        {id: 1, name: 'John Smith', type: 'Individual', influence: 5, support: 4},
        {id: 2, name: 'Mary Johnson', type: 'Individual', influence: 4, support: 5},
        {id: 3, name: 'Tech Corp', type: 'Organization', influence: 3, support: 2},
        {id: 4, name: 'Community Group', type: 'Organization', influence: 2, support: 5},
        {id: 5, name: 'City Council', type: 'Organization', influence: 5, support: 3}
      ],
      links: [
        {source: 1, target: 2, type: 'Colleague', strength: 2},
        {source: 2, target: 3, type: 'Advisor', strength: 3},
        {source: 1, target: 4, type: 'Member', strength: 1},
        {source: 3, target: 5, type: 'Reports To', strength: 2},
        {source: 4, target: 5, type: 'Advocate', strength: 1}
      ]
    };

    this.initializeVisualization(demoData);
  }

  initializeVisualization(data) {
    this.visualization = new PowerMapVisualization('powermap-container', data);

    // Initialize relationship type checkboxes
    this.initializeRelationshipTypes(data.links);
  }

  initializeRelationshipTypes(links) {
    const types = [...new Set(links.map(link => link.type))];
    const container = document.getElementById('relationship-types');

    if (container) {
      container.innerHTML = '';
      types.forEach(type => {
        const div = document.createElement('div');
        div.className = 'filter-checkbox';
        div.innerHTML = `
          <input type="checkbox" class="relationship-checkbox" value="${type}" id="rel-${type}" checked>
          <label for="rel-${type}">${type}</label>
        `;
        container.appendChild(div);
      });

      // Re-attach event listeners
      document.querySelectorAll('.relationship-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', () => {
          this.updateRelationshipTypeFilter();
        });
      });
    }
  }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  window.powermapController = new PowerMapController();
});
