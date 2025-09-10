class PowerMapVisualization {
  constructor(containerId, data) {
    this.containerId = containerId;
    this.originalData = data;
    this.filteredData = {
      ...data
    };
    this.width = 800;
    this.height = 600;
    this.simulation = null;
    this.svg = null;
    this.tooltip = null;
    this.currentTransform = d3.zoomIdentity;
    this.zoom = null;

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
    container.selectAll("*").remove(); // Clear any existing content

    this.width = container.node().getBoundingClientRect().width || 800;
    this.height = container.node().getBoundingClientRect().height || 600;

    this.svg = container
      .append('svg')
      .attr('width', this.width)
      .attr('height', this.height)
      .attr('class', 'powermap-svg');

    // Create main group for zoom/pan
    this.mainGroup = this.svg.append('g').attr('class', 'main-group');

    // Create groups for different elements in proper order
    this.linksGroup = this.mainGroup.append('g').attr('class', 'links');
    this.nodesGroup = this.mainGroup.append('g').attr('class', 'nodes');
    this.labelsGroup = this.mainGroup.append('g').attr('class', 'labels');

    // Add arrow markers for links
    this.svg.append('defs').selectAll('marker')
      .data(['arrowhead'])
      .enter().append('marker')
      .attr('id', 'arrowhead')
      .attr('viewBox', '0 -5 10 10')
      .attr('refX', 25)
      .attr('refY', 0)
      .attr('orient', 'auto')
      .attr('markerWidth', 8)
      .attr('markerHeight', 8)
      .append('svg:path')
      .attr('d', 'M 0,-5 L 10 ,0 L 0,5')
      .attr('fill', '#999')
      .style('stroke', 'none');
  }

  setupTooltip() {
    // Remove existing tooltip if any
    d3.select('.powermap-tooltip').remove();

    this.tooltip = d3.select('body')
      .append('div')
      .attr('class', 'powermap-tooltip')
      .style('opacity', 0)
      .style('position', 'absolute')
      .style('background', 'rgba(0, 0, 0, 0.9)')
      .style('color', 'white')
      .style('padding', '12px')
      .style('border-radius', '8px')
      .style('font-size', '14px')
      .style('pointer-events', 'none')
      .style('z-index', '1000');
  }

  setupSimulation() {
    this.simulation = d3.forceSimulation()
      .force('link', d3.forceLink().id(d => d.id).distance(80).strength(0.1))
      .force('charge', d3.forceManyBody().strength(-300))
      .force('center', d3.forceCenter(this.width / 2, this.height / 2))
      .force('collision', d3.forceCollide().radius(d => this.getNodeRadius(d) + 5));
  }

  setupZoom() {
    this.zoom = d3.zoom()
      .scaleExtent([0.1, 4])
      .on('zoom', () => {
        this.currentTransform = d3.event.transform;
        this.mainGroup.attr('transform', d3.event.transform);
      });

    this.svg.call(this.zoom);
  }

  setupKeyboardShortcuts() {
    d3.select('body').on('keydown', (event) => {
      if (event.ctrlKey || event.metaKey) {
        switch (event.key) {
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
    // Process and fix data before rendering
    this.processData();
    this.renderLinks();
    this.renderNodes();
    this.renderLabels();
    this.updateSimulation();
    this.updateStats();
  }

  processData() {
    // Ensure nodes have proper IDs and properties
    this.filteredData.nodes = this.filteredData.nodes.map(node => ({
      ...node,
      id: String(node.id),
      name: node.name || `Contact ${node.id}`,
      influence: parseInt(node.influence) || 1,
      support: parseInt(node.support) || 1,
      type: node.type || 'Individual'
    }));

    // Fix link references and ensure they point to actual nodes
    const nodeIds = new Set(this.filteredData.nodes.map(n => n.id));
    this.filteredData.links = this.filteredData.links
      .filter(link => {
        const sourceId = String(link.source.id || link.source);
        const targetId = String(link.target.id || link.target);
        return nodeIds.has(sourceId) && nodeIds.has(targetId) && sourceId !== targetId;
      })
      .map(link => ({
        ...link,
        source: String(link.source.id || link.source),
        target: String(link.target.id || link.target),
        type: link.type || 'Related to',
        strength: parseInt(link.strength) || 1
      }));
  }

  renderLinks2() {
    const links = this.linksGroup
      .selectAll('.link')
      .data(this.filteredData.links, d => `${d.source}-${d.target}`);

    links.exit().remove();

    const linksEnter = links.enter()
      .append('line')
      .attr('class', 'link')
      .attr('stroke', '#F54927')
      .attr('stroke-opacity', 0.6)
      .attr('stroke-width', d => this.getLinkWidth(d))
      .attr('marker-end', 'url(#arrowhead)')
      .style('cursor', 'pointer');

    // Add hover effects for links
    linksEnter
      .on('mouseover', (event, d) => this.showLinkTooltip(event, d))
      .on('mouseout', () => this.hideTooltip());

    this.links = linksEnter.merge(links);
  }

  renderLinks() {
    const links = this.linksGroup
      .selectAll('.link')
      .data(this.filteredData.links, d => `${d.source}-${d.target}`);

    links.exit().remove();

    const linksEnter = links.enter()
      .append('line')
      .attr('class', 'link')
      .attr('stroke', '#FFA500')
      .attr('stroke-opacity', 0.6)
      .attr('stroke-width', d => this.getLinkWidth(d))
      .attr('marker-end', 'url(#arrowhead)')
      .style('cursor', 'pointer');

    // Hover tooltip
    linksEnter
      .on('mouseover', (event, d) => this.showLinkTooltip(event, d))
      .on('mouseout', () => this.hideTooltip());

    this.links = linksEnter.merge(links);

    // ---- ADD LABELS ----
    const linkLabels = this.linksGroup
      .selectAll('.link-label')
      .data(this.filteredData.links, d => `${d.source}-${d.target}`);

    linkLabels.exit().remove();

    const labelsEnter = linkLabels.enter()
      .append('text')
      .attr('class', 'link-label')
      .attr('text-anchor', 'middle')
      .attr('dy', -2) // shift slightly above line
      .style('font-size', '10px')
      .style('fill', '#555')
      .text(d => d.type);

    this.linkLabels = labelsEnter.merge(linkLabels);
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
      .on('mouseover', (event, d) => this.showNodeTooltip(event, d))
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
    if (this.links) {
      this.links
        .attr('x1', d => d.source.x)
        .attr('y1', d => d.source.y)
        .attr('x2', d => d.target.x)
        .attr('y2', d => d.target.y);
    }

    if (this.nodes) {
      this.nodes
        .attr('cx', d => d.x)
        .attr('cy', d => d.y);
    }

    if (this.labels) {
      this.labels
        .attr('x', d => d.x)
        .attr('y', d => d.y + this.getNodeRadius(d) + 15);
    }
    this.linkLabels
      .attr('x', d => (d.source.x + d.target.x) / 2)
      .attr('y', d => (d.source.y + d.target.y) / 2);
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
    return 8 + (d.influence * 3);
  }

  getNodeColor(d) {
    if (d.influence >= 4) return this.colors.high;
    if (d.influence >= 3) return this.colors.medium;
    return this.colors.low;
  }

  colorScale(d) {
    const a = d3.scaleLinear()
      .domain([1, 3])         // input: strength range
      .range(['#ccc', '#F54927']); // output: low → high color
    console.log('Color scale for strength', d.strength, 'is', a(d.strength));
    return a;
  }
  getLinkWidth(d) {
    return Math.max(1, (d.strength || 1) * 2);
  }

  showNodeTooltip(event, d) {
    const connections = this.getConnections(d);
    const relationshipDetails = this.getRelationshipDetails(d);

    this.tooltip.transition()
      .duration(200)
      .style('opacity', 0.9);

    this.tooltip.html(`
<div class="tooltip-content">
<h4>${d.name}</h4>
<p><strong>Type:</strong> ${d.type}</p>
<p><strong>Influence:</strong> ${d.influence}/5 ${this.getInfluenceLabel(d.influence)}</p>
<p><strong>Support:</strong> ${d.support}/5 ${this.getSupportLabel(d.support)}</p>
<p><strong>Connections:</strong> ${connections.length}</p>
${relationshipDetails.length > 0 ? '<p><strong>Relationships:</strong></p><ul>' + relationshipDetails.map(r => `<li>${r}</li>`).join('') + '</ul>' : ''}
</div>
`)
      .style('left', (event.pageX + 10) + 'px')
      .style('top', (event.pageY - 28) + 'px');
  }

  showLinkTooltip(event, d) {
    const sourceNode = this.filteredData.nodes.find(n => n.id === d.source);
    const targetNode = this.filteredData.nodes.find(n => n.id === d.target);

    this.tooltip.transition()
      .duration(200)
      .style('opacity', 0.9);

    this.tooltip.html(`
<div class="tooltip-content">
<h4>Relationship</h4>
<p><strong>From:</strong> ${sourceNode ? sourceNode.name : 'Unknown'}</p>
<p><strong>To:</strong> ${targetNode ? targetNode.name : 'Unknown'}</p>
<p><strong>Type:</strong> ${d.type}</p>
<p><strong>Strength:</strong> ${d.strength}/3</p>
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

  getInfluenceLabel(influence) {
    const labels = ['', '(Low)', '(Medium-Low)', '(Medium)', '(High)', '(Very High)'];
    return labels[influence] || '';
  }

  getSupportLabel(support) {
    const labels = ['', '(Strong Opposition)', '(Opposition)', '(Neutral)', '(Support)', '(Strong Support)'];
    return labels[support] || '';
  }

  getRelationshipDetails(node) {
    const relationships = [];
    this.filteredData.links.forEach(link => {
      const sourceId = link.source.id || link.source;
      const targetId = link.target.id || link.target;

      if (sourceId === node.id) {
        const targetNode = this.filteredData.nodes.find(n => n.id === targetId);
        if (targetNode) {
          relationships.push(`${link.type} → ${targetNode.name}`);
        }
      } else if (targetId === node.id) {
        const sourceNode = this.filteredData.nodes.find(n => n.id === sourceId);
        if (sourceNode) {
          relationships.push(`${sourceNode.name} → ${link.type}`);
        }
      }
    });
    return relationships;
  }

  highlightConnections(node) {
    const connections = this.getConnections(node);
    const connectedIds = new Set([node.id, ...connections.map(c => c.id)]);

    // Reset all nodes and links
    this.nodes
      .style('opacity', d => connectedIds.has(d.id) ? 1 : 0.3)
      .attr('stroke-width', d => d.id === node.id ? 4 : 2);

    this.links
      .style('opacity', d => {
        const sourceId = d.source.id || d.source;
        const targetId = d.target.id || d.target;
        return (sourceId === node.id || targetId === node.id) ? 1 : 0.1;
      })
      .attr('stroke-width', d => {
        const sourceId = d.source.id || d.source;
        const targetId = d.target.id || d.target;
        return (sourceId === node.id || targetId === node.id) ? 3 : this.getLinkWidth(d);
      });

    this.labels
      .style('opacity', d => connectedIds.has(d.id) ? 1 : 0.3);
  }

  getConnections(node) {
    return this.filteredData.nodes.filter(n => {
      return this.filteredData.links.some(link => {
        const sourceId = link.source.id || link.source;
        const targetId = link.target.id || link.target;
        return ((sourceId === node.id && targetId === n.id) ||
          (targetId === node.id && sourceId === n.id));
      });
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
      relationshipDepth = 0,
      searchTerm = ''
    } = filters;

    // Start with all nodes
    let filteredNodes = this.originalData.nodes.filter(node => {
      const influenceMatch = node.influence >= influenceMin;
      const supportMatch = node.support >= supportMin;
      const searchMatch = searchTerm === '' ||
        node.name.toLowerCase().includes(searchTerm.toLowerCase());

      return influenceMatch && supportMatch && searchMatch;
    });

    // Apply relationship depth filter if specified
    if (relationshipDepth > 0) {
      filteredNodes = this.filterByRelationshipDepth(filteredNodes, relationshipDepth);
    }

    this.filteredData.nodes = filteredNodes;

    // Get filtered node IDs
    const nodeIds = new Set(this.filteredData.nodes.map(n => String(n.id)));

    // Filter links
    this.filteredData.links = this.originalData.links.filter(link => {
      const sourceId = String(link.source.id || link.source);
      const targetId = String(link.target.id || link.target);
      const sourceExists = nodeIds.has(sourceId);
      const targetExists = nodeIds.has(targetId);
      const typeMatch = relationshipTypes.length === 0 ||
        relationshipTypes.includes(link.type);

      return sourceExists && targetExists && typeMatch;
    });

    this.render();
  }

  filterByRelationshipDepth(nodes, maxDepth) {
    // This is a simplified implementation
    // In a real scenario, you might want to start from specific seed nodes
    const nodeConnections = new Map();

    // Count connections for each node
    nodes.forEach(node => {
      const connections = this.originalData.links.filter(link => {
        const sourceId = String(link.source.id || link.source);
        const targetId = String(link.target.id || link.target);
        return sourceId === String(node.id) || targetId === String(node.id);
      });
      nodeConnections.set(String(node.id), connections.length);
    });

    // Filter nodes based on connection count
    return nodes.filter(node => {
      const connectionCount = nodeConnections.get(String(node.id)) || 0;
      return connectionCount >= maxDepth;
    });
  }

  // Zoom methods - Fixed to work properly
  zoomIn() {
    this.svg.transition().duration(300).call(
      this.zoom.scaleBy, 1.5
    );
  }

  zoomOut() {
    this.svg.transition().duration(300).call(
      this.zoom.scaleBy, 0.75
    );
  }

  resetZoom() {
    this.svg.transition().duration(500).call(
      this.zoom.transform,
      d3.zoomIdentity
    );
  }

  centerView() {
    try {
      if (!this.filteredData.nodes.length) return;

      const bounds = this.getBounds();
      const fullWidth = this.width;
      const fullHeight = this.height;
      const width = bounds.width;
      const height = bounds.height;
      const midX = bounds.x + width / 2;
      const midY = bounds.y + height / 2;

      if (width === 0 || height === 0) return;

      const scale = Math.min(fullWidth / width, fullHeight / height) * 0.8;
      const translate = [fullWidth / 2 - scale * midX, fullHeight / 2 - scale * midY];
      const newTransform = d3.zoomIdentity.translate(translate[0], translate[1]).scale(scale);

      if (this.zoom && this.zoom.transform) {
        this.svg.transition().duration(750).call(
          this.zoom.transform,
          newTransform
        );
      } else {
        // Fallback manual center
        this.currentTransform = newTransform;
        this.mainGroup.transition().duration(750).attr('transform', newTransform);
      }
    } catch (error) {
      console.error('Error centering view:', error);
    }
  }

  getBounds() {
    const nodes = this.filteredData.nodes;
    if (nodes.length === 0) return {
      x: 0,
      y: 0,
      width: 0,
      height: 0
    };

    const xs = nodes.map(d => d.x || 0);
    const ys = nodes.map(d => d.y || 0);
    const minX = Math.min(...xs);
    const maxX = Math.max(...xs);
    const minY = Math.min(...ys);
    const maxY = Math.max(...ys);

    return {
      x: minX,
      y: minY,
      width: maxX - minX,
      height: maxY - minY
    };
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

    return {
      total,
      highInfluence,
      supporters,
      opposition
    };
  }

  updateStatsDisplay(stats) {
    const elements = {
      'stat-total': stats.total,
      'stat-influence': stats.highInfluence,
      'stat-supporters': stats.supporters,
      'stat-opposition': stats.opposition
    };

    Object.entries(elements).forEach(([id, value]) => {
      const element = document.getElementById(id);
      if (element) {
        element.textContent = value;
      }
    });
  }

  // Search functionality
  searchStakeholder(searchTerm) {
    if (!searchTerm) {
      this.clearSearch();
      return;
    }

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
        const scale = this.currentTransform ? this.currentTransform.k : 1;
        const translate = [
          this.width / 2 - scale * firstMatch.x,
          this.height / 2 - scale * firstMatch.y
        ];

        this.svg.transition().duration(500).call(
          this.zoom.transform,
          d3.zoomIdentity.translate(translate[0], translate[1]).scale(scale)
        );
      }
    }
  }

  clearSearch() {
    if (this.nodes) {
      this.nodes
        .style('opacity', 1)
        .attr('stroke-width', 2);
    }
    if (this.links) {
      this.links
        .style('opacity', 0.6)
        .attr('stroke-width', d => this.getLinkWidth(d));
    }
    if (this.labels) {
      this.labels
        .style('opacity', 1);
    }
  }

  // Update data method
  updateData(newData) {
    console.log('Updating data with:', newData);
    this.originalData = newData;
    this.filteredData = {
      ...newData
    };
    this.render();
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

  // Export functionality
  exportToCSV() {
    const csvContent = this.generateCSVContent();
    this.downloadCSV(csvContent, 'powermap-export.csv');
  }

  generateCSVContent() {
    const headers = ['Name', 'Type', 'Influence', 'Support', 'Connections', 'Relationships'];
    const rows = [headers];

    this.filteredData.nodes.forEach(node => {
      const connections = this.getConnections(node).length;
      const relationships = this.getRelationshipDetails(node).join('; ');
      rows.push([
        `"${node.name}"`,
        node.type,
        node.influence,
        node.support,
        connections,
        `"${relationships}"`
      ]);
    });

    return rows.map(row => row.join(',')).join('\n');
  }

  downloadCSV(content, filename) {
    const blob = new Blob([content], {
      type: 'text/csv;charset=utf-8;'
    });
    const link = document.createElement('a');

    if (link.download !== undefined) {
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', filename);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    }
  }
}

// Enhanced PowerMap Controller with all fixes
class EnhancedPowerMapController {
  constructor() {
    this.visualization = null;
    this.filters = {
      influenceMin: 1,
      supportMin: 1,
      relationshipTypes: [],
      relationshipDepth: 0,
      searchTerm: ''
    };
    this.init();
  }

  init() {
    console.log('Initializing PowerMap Controller...');
    this.setupEventListeners();
    this.showLoading();

    // Small delay to ensure DOM is ready
    setTimeout(() => {
      this.loadData();
    }, 100);
  }

  setupEventListeners() {
    this.setupFilterControls();
    this.setupSearchControls();
    this.setupViewControls();
    this.setupExportControls();
    this.setupModalControls();

    // Window resize with debouncing
    let resizeTimeout;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(() => {
        this.visualization?.resize();
      }, 150);
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', (event) => {
      if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
        return; // Don't interfere with form inputs
      }

      if (event.ctrlKey || event.metaKey) {
        switch(event.key) {
          case '=':
          case '+':
            event.preventDefault();
            this.visualization?.zoomIn();
            break;
          case '-':
            event.preventDefault();
            this.visualization?.zoomOut();
            break;
          case '0':
            event.preventDefault();
            this.visualization?.resetZoom();
            break;
          case 'f':
          case 'F':
            event.preventDefault();
            document.getElementById('search-input')?.focus();
            break;
        }
      }

      // ESC key to clear search or close modals
      if (event.key === 'Escape') {
        this.clearSearch();
        this.closeContactModal();
      }
    });
  }

  setupFilterControls() {
    // Influence slider
    const influenceSlider = document.getElementById('influence-slider');
    const influenceValue = document.getElementById('influence-value');
    if (influenceSlider && influenceValue) {
      influenceSlider.addEventListener('input', (e) => {
        this.filters.influenceMin = parseInt(e.target.value);
        influenceValue.textContent = e.target.value;
        this.applyFiltersDebounced();
      });
    }

    // Support slider
    const supportSlider = document.getElementById('support-slider');
    const supportValue = document.getElementById('support-value');
    if (supportSlider && supportValue) {
      supportSlider.addEventListener('input', (e) => {
        this.filters.supportMin = parseInt(e.target.value);
        supportValue.textContent = e.target.value;
        this.applyFiltersDebounced();
      });
    }

    // Relationship depth slider
    const depthSlider = document.getElementById('relationship-depth-slider');
    const depthValue = document.getElementById('relationship-depth-value');
    if (depthSlider && depthValue) {
      depthSlider.addEventListener('input', (e) => {
        this.filters.relationshipDepth = parseInt(e.target.value);
        depthValue.textContent = e.target.value;
        this.applyFiltersDebounced();
      });
    }

    // Relationship type controls
    const selectAllBtn = document.getElementById('select-all-relationships');
    const deselectAllBtn = document.getElementById('deselect-all-relationships');
    if (selectAllBtn) {
      selectAllBtn.addEventListener('click', () => this.selectAllRelationshipTypes());
    }
    if (deselectAllBtn) {
      deselectAllBtn.addEventListener('click', () => this.deselectAllRelationshipTypes());
    }

    // Reset filters
    const resetBtn = document.getElementById('reset-filters');
    if (resetBtn) {
      resetBtn.addEventListener('click', () => this.resetFilters());
    }
  }

  setupSearchControls() {
    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search');

    if (searchInput) {
      let searchTimeout;
      searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          this.filters.searchTerm = e.target.value;
          this.visualization?.searchStakeholder(e.target.value);
          this.updateSearchState(e.target.value);
        }, 300); // Debounce search
      });

      searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          this.clearSearch();
        }
      });
    }

    if (clearSearchBtn) {
      clearSearchBtn.addEventListener('click', () => {
        this.clearSearch();
      });
    }
  }

  setupViewControls() {
    const controls = {
      'zoom-in': () => this.visualization?.zoomIn(),
      'zoom-out': () => this.visualization?.zoomOut(),
      'reset-view': () => this.visualization?.resetZoom(),
      'center-view': () => this.visualization?.centerView()
    };

    Object.entries(controls).forEach(([id, handler]) => {
      const element = document.getElementById(id);
      if (element) {
        element.addEventListener('click', (e) => {
          e.preventDefault();
          handler();
        });
      }
    });
  }

  setupExportControls() {
    const exportCsv = document.getElementById('export-csv');
    const exportPng = document.getElementById('export-png');

    if (exportCsv) {
      exportCsv.addEventListener('click', (e) => {
        e.preventDefault();
        this.visualization?.exportToCSV();
      });
    }

    if (exportPng) {
      exportPng.addEventListener('click', (e) => {
        e.preventDefault();
        this.exportToPNG();
      });
    }
  }

  setupModalControls() {
    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
      const modal = document.getElementById('contact-details-modal');
      if (event.target === modal) {
        this.closeContactModal();
      }
    });
  }

  // Debounced filter application
  applyFiltersDebounced() {
    if (this.filterTimeout) {
      clearTimeout(this.filterTimeout);
    }
    this.filterTimeout = setTimeout(() => {
      this.applyFilters();
    }, 150);
  }

  showLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
      overlay.style.display = 'flex';
    }
  }

  hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
      overlay.style.display = 'none';
    }
  }

  loadData() {
    try {
      console.log('Loading data...', window.powermapData);

      if (window.powermapData && window.powermapData.nodes && window.powermapData.nodes.length > 0) {
        console.log('Using provided data with', window.powermapData.nodes.length, 'nodes');
        this.initializeVisualization(window.powermapData);
      } else {
        console.log('No valid data found, using demo data');
        //this.loadDemoData();
      }
    } catch (error) {
      console.error('Error loading data:', error);
      //this.loadDemoData();
    } finally {
      this.hideLoading();
    }
  }

  loadDemoData() {
    console.log('Loading demo data...');
    const demoData = {
      nodes: [
        {id: 1, name: 'John Smith', type: 'Individual', influence: 5, support: 4},
        {id: 2, name: 'Mary Johnson', type: 'Individual', influence: 4, support: 5},
        {id: 3, name: 'Tech Corp', type: 'Organization', influence: 3, support: 2},
        {id: 4, name: 'Community Group', type: 'Organization', influence: 2, support: 5},
        {id: 5, name: 'City Council', type: 'Organization', influence: 5, support: 3},
        {id: 6, name: 'Local Media', type: 'Organization', influence: 4, support: 3},
        {id: 7, name: 'Sarah Wilson', type: 'Individual', influence: 3, support: 4},
        {id: 8, name: 'Green Alliance', type: 'Organization', influence: 2, support: 5}
      ],
      links: [
        {source: 1, target: 2, type: 'Colleague', strength: 2},
        {source: 2, target: 3, type: 'Advisor', strength: 3},
        {source: 1, target: 4, type: 'Member', strength: 1},
        {source: 3, target: 5, type: 'Reports To', strength: 2},
        {source: 4, target: 5, type: 'Advocate', strength: 1},
        {source: 6, target: 1, type: 'Interviews', strength: 2},
        {source: 7, target: 4, type: 'Volunteer', strength: 2},
        {source: 8, target: 7, type: 'Partner', strength: 3},
        {source: 2, target: 6, type: 'Contact', strength: 1},
        {source: 5, target: 6, type: 'Official Source', strength: 2}
      ]
    };
    this.initializeVisualization(demoData);
  }

  initializeVisualization(data) {
    console.log('Initializing visualization with data:', data);

    try {
      //const cleanedData = this.cleanData(data);
      const cleanedData = data;
      console.log('Cleaned data:', cleanedData);

      this.visualization = new PowerMapVisualization('powermap-container', cleanedData);
      this.initializeRelationshipTypes(cleanedData.links);
      this.updateQuickStats();

      console.log('Visualization initialized successfully');
    } catch (error) {
      console.error('Error initializing visualization:', error);
    }
  }

  cleanData(data) {
    // Ensure nodes have proper structure
    const cleanedNodes = data.nodes.map(node => ({
      ...node,
      id: String(node.id),
      name: node.name && node.name.trim() !== '' ? node.name : `Contact ${node.id}`,
      influence: parseInt(node.influence) || 1,
      support: parseInt(node.support) || 1,
      type: node.type || 'Individual'
    }));

    // Create node ID set for validation
    const nodeIds = new Set(cleanedNodes.map(n => n.id));

    // Clean and validate links
    const cleanedLinks = data.links.filter(link => {
      const sourceId = String(link.source.id || link.source);
      const targetId = String(link.target.id || link.target);

      // Validate that both source and target exist and are different
      return sourceId !== targetId &&
          nodeIds.has(sourceId) &&
          nodeIds.has(targetId);
    }).map(link => ({
      ...link,
      source: String(link.source.id || link.source),
      target: String(link.target.id || link.target),
      type: link.type || 'Related to',
      strength: parseInt(link.strength) || 1
    }));

    console.log(`Cleaned data: ${cleanedNodes.length} nodes, ${cleanedLinks.length} links`);

    return {
      nodes: cleanedNodes,
      links: cleanedLinks
    };
  }

  initializeRelationshipTypes(links) {
    const types = [...new Set(links.map(link => link.type))];
    const container = document.getElementById('relationship-types');

    if (container) {
      container.innerHTML = '';
      types.forEach(type => {
        const safeId = type.replace(/\s+/g, '-').replace(/[^a-zA-Z0-9-]/g, '');
        const div = document.createElement('div');
        div.className = 'filter-checkbox';
        div.innerHTML = `
          <input type="checkbox" class="relationship-checkbox" value="${type}" id="rel-${safeId}" checked>
          <label for="rel-${safeId}">${type}</label>
        `;
        container.appendChild(div);
      });

      // Attach event listeners
      document.querySelectorAll('.relationship-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', () => this.updateRelationshipTypeFilter());
      });

      // Initialize filter state
      this.updateRelationshipTypeFilter();
    }
  }

  updateRelationshipTypeFilter() {
    this.filters.relationshipTypes = Array.from(
        document.querySelectorAll('.relationship-checkbox:checked')
    ).map(cb => cb.value);
    this.applyFiltersDebounced();
  }

  selectAllRelationshipTypes() {
    document.querySelectorAll('.relationship-checkbox').forEach(cb => {
      cb.checked = true;
    });
    this.updateRelationshipTypeFilter();
  }

  deselectAllRelationshipTypes() {
    document.querySelectorAll('.relationship-checkbox').forEach(cb => {
      cb.checked = false;
    });
    this.updateRelationshipTypeFilter();
  }

  resetFilters() {
    this.filters = {
      influenceMin: 1,
      supportMin: 1,
      relationshipTypes: [],
      relationshipDepth: 0,
      searchTerm: ''
    };

    // Reset UI elements
    const elements = {
      'influence-slider': 1,
      'support-slider': 1,
      'relationship-depth-slider': 0,
      'search-input': ''
    };

    Object.entries(elements).forEach(([id, value]) => {
      const element = document.getElementById(id);
      if (element) element.value = value;
    });

    // Reset value displays
    const valueElements = {
      'influence-value': '1',
      'support-value': '1',
      'relationship-depth-value': '0'
    };

    Object.entries(valueElements).forEach(([id, value]) => {
      const element = document.getElementById(id);
      if (element) element.textContent = value;
    });

    // Select all relationship types
    this.selectAllRelationshipTypes();
    this.clearSearch();
  }

  updateSearchState(searchTerm) {
    const searchContainer = document.querySelector('.search-container');
    const clearBtn = document.getElementById('clear-search');

    if (searchContainer) {
      if (searchTerm) {
        searchContainer.classList.add('search-active');
        if (clearBtn) clearBtn.style.display = 'block';
      } else {
        searchContainer.classList.remove('search-active');
        if (clearBtn) clearBtn.style.display = 'none';
      }
    }
  }

  clearSearch() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
      searchInput.value = '';
    }
    this.filters.searchTerm = '';
    this.visualization?.clearSearch();
    this.updateSearchState('');
  }

  applyFilters() {
    if (!this.visualization) return;

    console.log('Applying filters:', this.filters);
    this.visualization.applyFilters(this.filters);
    this.updateQuickStats();
  }

  updateQuickStats() {
    if (!this.visualization) return;

    try {
      const stats = this.visualization.calculateStats();
      const filteredNodes = this.visualization.filteredData.nodes;
      const filteredLinks = this.visualization.filteredData.links;

      // Calculate additional metrics
      const avgInfluence = filteredNodes.length > 0
          ? (filteredNodes.reduce((sum, n) => sum + n.influence, 0) / filteredNodes.length).toFixed(1)
          : 0;

      const networkDensity = this.calculateNetworkDensity(filteredNodes, filteredLinks);

      // Update quick stats
      const elements = {
        'filtered-count': filteredNodes.length,
        'relationships-count': filteredLinks.length,
        'avg-influence': avgInfluence,
        'network-density': networkDensity
      };

      Object.entries(elements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
          element.textContent = value;
        }
      });
    } catch (error) {
      console.error('Error updating quick stats:', error);
    }
  }

  calculateNetworkDensity(nodes, links) {
    if (nodes.length < 2) return '0%';
    const maxPossibleLinks = nodes.length * (nodes.length - 1) / 2;
    const density = (links.length / maxPossibleLinks) * 100;
    return density.toFixed(1) + '%';
  }

  exportToPNG() {
    const svg = document.querySelector('#powermap-container svg');
    if (!svg) {
      console.error('No SVG found for export');
      return;
    }

    try {
      // Clone and prepare SVG for export
      const svgClone = svg.cloneNode(true);
      svgClone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');

      // Set explicit dimensions
      const bbox = svg.getBoundingClientRect();
      svgClone.setAttribute('width', bbox.width);
      svgClone.setAttribute('height', bbox.height);

      // Create canvas
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      const pixelRatio = window.devicePixelRatio || 1;

      canvas.width = bbox.width * pixelRatio;
      canvas.height = bbox.height * pixelRatio;
      canvas.style.width = bbox.width + 'px';
      canvas.style.height = bbox.height + 'px';

      ctx.scale(pixelRatio, pixelRatio);
      ctx.fillStyle = 'white';
      ctx.fillRect(0, 0, bbox.width, bbox.height);

      // Convert SVG to data URL
      const data = new XMLSerializer().serializeToString(svgClone);
      const svgBlob = new Blob([data], { type: 'image/svg+xml;charset=utf-8' });
      const url = URL.createObjectURL(svgBlob);

      const img = new Image();
      img.onload = function() {
        ctx.drawImage(img, 0, 0, bbox.width, bbox.height);
        URL.revokeObjectURL(url);

        // Download the image
        canvas.toBlob(function(blob) {
          const link = document.createElement('a');
          link.download = `powermap-${new Date().toISOString().split('T')[0]}.png`;
          link.href = URL.createObjectURL(blob);
          link.click();
          URL.revokeObjectURL(link.href);
        }, 'image/png', 1.0);
      };

      img.onerror = function() {
        console.error('Error loading SVG for export');
        URL.revokeObjectURL(url);
      };

      img.src = url;
    } catch (error) {
      console.error('Error exporting PNG:', error);
      alert('Export failed. Please try again.');
    }
  }

  showContactDetails(contactId) {
    if (!this.visualization) return;

    const contact = this.visualization.filteredData.nodes.find(n => n.id === String(contactId));
    if (!contact) return;

    const modal = document.getElementById('contact-details-modal');
    const content = document.getElementById('contact-details-content');

    if (modal && content) {
      try {
        const connections = this.visualization.getConnections(contact);
        const relationships = this.visualization.getRelationshipDetails(contact);

        content.innerHTML = `
          <div class="contact-details">
            <h3>${this.escapeHtml(contact.name)}</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <strong>Contact Type:</strong> ${this.escapeHtml(contact.type)}
              </div>
              <div class="detail-item">
                <strong>Influence Level:</strong> ${contact.influence}/5 ${this.getInfluenceLabel(contact.influence)}
              </div>
              <div class="detail-item">
                <strong>Support Level:</strong> ${contact.support}/5 ${this.getSupportLabel(contact.support)}
              </div>
              <div class="detail-item">
                <strong>Direct Connections:</strong> ${connections.length}
              </div>
            </div>
            ${relationships.length > 0 ? `
              <div class="relationships-section">
                <h4>Network Relationships:</h4>
                <ul class="relationships-list">
                  ${relationships.map(r => `<li>${this.escapeHtml(r)}</li>`).join('')}
                </ul>
              </div>
            ` : '<p><em>No direct relationships found.</em></p>'}
            <div class="action-buttons" style="margin-top: 20px; text-align: center;">
              <button class="btn btn-primary" onclick="window.powermapController.highlightContactNetwork('${contact.id}')">
                Highlight Network
              </button>
              <button class="btn btn-secondary" onclick="window.powermapController.centerOnContact('${contact.id}')">
                Center View
              </button>
            </div>
          </div>
        `;

        modal.style.display = 'block';
      } catch (error) {
        console.error('Error showing contact details:', error);
      }
    }
  }

  highlightContactNetwork(contactId) {
    if (!this.visualization) return;

    const contact = this.visualization.filteredData.nodes.find(n => n.id === String(contactId));
    if (contact) {
      this.visualization.highlightConnections(contact);
      this.closeContactModal();
    }
  }

  centerOnContact(contactId) {
    if (!this.visualization) return;

    const contact = this.visualization.filteredData.nodes.find(n => n.id === String(contactId));
    if (contact && contact.x && contact.y) {
      // Center the view on this contact
      const scale = this.visualization.currentTransform ? this.visualization.currentTransform.k : 1;
      const translate = [
        this.visualization.width / 2 - scale * contact.x,
        this.visualization.height / 2 - scale * contact.y
      ];

      this.visualization.svg.transition().duration(750).call(
          this.visualization.zoom.transform,
          d3.zoomIdentity.translate(translate[0], translate[1]).scale(scale)
      );

      this.closeContactModal();
    }
  }

  closeContactModal() {
    const modal = document.getElementById('contact-details-modal');
    if (modal) {
      modal.style.display = 'none';
    }
  }

  getInfluenceLabel(influence) {
    const labels = ['', '(Low)', '(Medium-Low)', '(Medium)', '(High)', '(Very High)'];
    return labels[influence] || '';
  }

  getSupportLabel(support) {
    const labels = ['', '(Strong Opposition)', '(Opposition)', '(Neutral)', '(Support)', '(Strong Support)'];
    return labels[support] || '';
  }

  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Public method to refresh data
  refreshData() {
    this.showLoading();
    setTimeout(() => {
      this.loadData();
    }, 100);
  }

  // Method to add new stakeholder programmatically
  addStakeholder(stakeholderData) {
    if (!this.visualization) return false;

    try {
      // Add to original data
      const newNode = {
        id: String(stakeholderData.id),
        name: stakeholderData.name,
        type: stakeholderData.type || 'Individual',
        influence: parseInt(stakeholderData.influence) || 1,
        support: parseInt(stakeholderData.support) || 1
      };

      this.visualization.originalData.nodes.push(newNode);

      // Add relationships if provided
      if (stakeholderData.relationships) {
        stakeholderData.relationships.forEach(rel => {
          this.visualization.originalData.links.push({
            source: String(rel.source),
            target: String(rel.target),
            type: rel.type || 'Related to',
            strength: parseInt(rel.strength) || 1
          });
        });
      }

      // Refresh visualization
      this.visualization.updateData(this.visualization.originalData);
      this.initializeRelationshipTypes(this.visualization.originalData.links);
      this.applyFilters();

      return true;
    } catch (error) {
      console.error('Error adding stakeholder:', error);
      return false;
    }
  }

  // Method to get current network statistics
  getNetworkStats() {
    if (!this.visualization) return null;

    const nodes = this.visualization.filteredData.nodes;
    const links = this.visualization.filteredData.links;

    return {
      totalNodes: nodes.length,
      totalLinks: links.length,
      averageInfluence: nodes.length > 0 ? nodes.reduce((sum, n) => sum + n.influence, 0) / nodes.length : 0,
      averageSupport: nodes.length > 0 ? nodes.reduce((sum, n) => sum + n.support, 0) / nodes.length : 0,
      networkDensity: this.calculateNetworkDensity(nodes, links),
      highInfluenceCount: nodes.filter(n => n.influence >= 4).length,
      supportersCount: nodes.filter(n => n.support >= 4).length,
      oppositionCount: nodes.filter(n => n.support <= 2).length
    };
  }
}
