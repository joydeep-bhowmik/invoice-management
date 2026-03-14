<!-- resources/views/components/chart.blade.php -->
@props([
    'data',
    'type' => 'line',
    'title' => null,
    'color' => 'oklch(70.7% .165 254.624)',
    'bgColor' => 'oklch(95.1% .026 236.824)',
])
@php
    $bgColor = $type == 'bar' ? $color : $bgColor;
@endphp

<div x-data="{
    chart: null,
    chartType: '{{ $type }}',
    chartTitle: '{{ $title }}',
    chartData: @js($data),

    // Computed properties
    get total() {
        return this.chartData.reduce((sum, item) => sum + item.value, 0);
    },

    get average() {
        return this.chartData.length > 0 ? this.total / this.chartData.length : 0;
    },

    get maxValue() {
        return Math.max(...this.chartData.map(item => item.value));
    },

    get maxLabel() {
        const maxItem = this.chartData.find(item => item.value === this.maxValue);
        return maxItem ? maxItem.label : '';
    },

    initChart() {
        this.$nextTick(() => {
            this.renderChart();
        });
    },

    renderChart() {
        // Destroy existing chart if it exists
        if (this.chart) {
            this.chart.destroy();
        }

        const ctx = this.$refs.chartCanvas.getContext('2d');

        // Prepare data for Chart.js
        const labels = this.chartData.map(item => item.label);
        const values = this.chartData.map(item => item.value);

        // Chart configuration
        const config = {
            type: this.chartType,
            data: {
                labels: labels,
                datasets: [{
                    label: this.chartTitle,
                    data: values,
                    backgroundColor: '{{ $bgColor }}',
                    borderColor: '{{ $color }}',
                    borderWidth: 2,
                    pointBackgroundColor: '{{ $color }}',
                    pointRadius: 4,
                    fill: this.chartType === 'line',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true
                        },
                        title: {
                            display: true,
                            text: 'Value'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                }
            }
        };

        // Create new chart
        this.chart = new Chart(ctx, config);
    },

    updateChartType() {
        this.renderChart();
    }
}" x-init="initChart()" {{ $attributes }}>
    <canvas x-ref="chartCanvas"></canvas>
</div>
