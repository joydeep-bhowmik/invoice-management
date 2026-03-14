@props([
    'data' => [],
    'showLegend' => true,
    'legendPosition' => 'right',
    'showTooltips' => true,
    'cutoutPercentage' => 0, // 0 for pie, >0 for doughnut
    'animate' => true,
])

<div x-data="{
    chart: null,
    chartData: @js($data),
    showLegend: {{ $showLegend ? 'true' : 'false' }},
    legendPosition: '{{ $legendPosition }}',
    cutoutPercentage: {{ $cutoutPercentage }},

    // Computed properties
    get total() {
        return this.chartData.reduce((sum, item) => sum + item.value, 0);
    },

    get percentages() {
        return this.chartData.map(item => {
            const percentage = this.total > 0 ? ((item.value / this.total) * 100).toFixed(1) : 0;
            return {
                label: item.label,
                value: item.value,
                percentage: percentage + '%',
                color: item.color
            };
        });
    },

    get dominantSegment() {
        if (this.chartData.length === 0) return null;
        const max = Math.max(...this.chartData.map(item => item.value));
        return this.chartData.find(item => item.value === max);
    },

    initChart() {
        if (this.chartData.length === 0) {
            console.warn('No data provided for pie chart');
            return;
        }

        this.$nextTick(() => {
            this.renderChart();
        });
    },

    renderChart() {
        // Destroy existing chart
        if (this.chart) {
            this.chart.destroy();
        }

        const ctx = this.$refs.chartCanvas?.getContext('2d');
        if (!ctx) return;

        // Prepare data for Chart.js
        const labels = this.chartData.map(item => item.label);
        const values = this.chartData.map(item => item.value);
        const backgroundColors = this.chartData.map(item => item.color || this.generateColor(item.label));
        const hoverColors = this.chartData.map(item => this.adjustColor(item.color || this.generateColor(item.label), 0.2));

        // Chart configuration
        const config = {
            type: this.cutoutPercentage > 0 ? 'doughnut' : 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: backgroundColors,
                    borderColor: 'white',
                    borderWidth: 2,
                    hoverBackgroundColor: hoverColors,
                    hoverBorderWidth: 3,
                    hoverBorderColor: '#f3f4f6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    animateScale: {{ $animate ? 'true' : 'false' }},
                    animateRotate: {{ $animate ? 'true' : 'false' }}
                },
                plugins: {
                    legend: {
                        display: this.showLegend,
                        position: this.legendPosition,
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 12,
                                family: `'Inter', 'Segoe UI' , sans-serif`
                            }
                        }
                    },
                    tooltip: {
                        enabled: {{ $showTooltips ? 'true' : 'false' }},
                        callbacks: {
                            label: (context) => {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const percentage = this.total > 0 ? ((value / this.total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    },
                    title: {
                        display: !!this.chartTitle,
                        text: this.chartTitle,
                        font: {
                            size: 16,
                            weight: 'bold'
                        },
                        padding: {
                            top: 10,
                            bottom: 30
                        }
                    }
                },
                cutout: this.cutoutPercentage + '%'
            }
        };

        this.chart = new Chart(ctx, config);
    },

    generateColor(label) {
        // Generate consistent color based on label
        const colors = [
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
            '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'
        ];
        const index = label.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
        return colors[index % colors.length];
    },

    adjustColor(color, amount) {
        // Lighten or darken color
        let usePound = false;

        if (color[0] === `#`) {
            color = color.slice(1);
            usePound = true;
        }
        const num = parseInt(color, 16);
        let r = (num >> 16) +
            (255 * amount);
        let g = ((num >> 8) & 0x00FF) + (255 * amount);
        let b = (num & 0x0000FF) + (255 * amount);

        r = Math.min(Math.max(0, r), 255);
        g = Math.min(Math.max(0, g), 255);
        b = Math.min(Math.max(0, b), 255);

        return (usePound ? `#` : ``) + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
    },
    exportChart() {
        if (!this.chart) return;
        const link = document.createElement('a');
        link.download = `${this.chartTitle.replace(/\s+/g, '_' )}_chart.png`;
        link.href = this.chart.toBase64Image();
        link.click();
    },
    updateData(newData) {
        this.chartData = newData;
        this.renderChart();
    }
}" x-init="initChart()" {{ $attributes }}>
    <canvas x-ref="chartCanvas"></canvas>
</div>
