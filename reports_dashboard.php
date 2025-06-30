<?php
session_start();
require_once 'includes/auth.php';
require_once 'config/database.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$user = getUserById($conn, $_SESSION['user_id']);

if (!$user || $user['role'] !== 'adviser') {
    // For now, let's restrict this to advisers
    // A more complex permission system could be implemented later
    echo "Access Denied. You do not have permission to view this page.";
    // Or redirect to another page
    // header("Location: studentDashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #334155;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Modern Sidebar */
        .sidebar {
            width: 320px;
            background: var(--sidebar-bg);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: var(--transition);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid #334155;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-header .user-info {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .sidebar-section {
            padding: 1.5rem;
        }

        .sidebar-section h3 {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 0.875rem 1rem;
            color: #e2e8f0;
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
            gap: 0.75rem;
        }

        .sidebar-nav a:hover {
            background: var(--sidebar-hover);
            color: white;
            transform: translateX(4px);
        }

        .sidebar-nav a.active {
            background: var(--accent-color);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .sidebar-nav a i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 320px;
            min-height: 100vh;
            background: transparent;
        }

        .top-bar {
            background: white;
            padding: 1.5rem 2rem;
            box-shadow: var(--card-shadow);
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .top-bar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .breadcrumb {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .content-area {
            padding: 2rem;
            max-width: 100%;
        }

        /* Modern Cards */
        .modern-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid #e2e8f0;
            transition: var(--transition);
            overflow: hidden;
        }

        .modern-card:hover {
            box-shadow: var(--card-shadow-hover);
            transform: translateY(-2px);
        }

        .card-header-modern {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header-modern h5 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body-modern {
            padding: 1.5rem;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
            padding: 1rem;
            background: #fafafa;
            border-radius: var(--border-radius);
            margin: 1rem 0;
        }

        /* Table Styling */
        .modern-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .modern-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            font-size: 0.875rem;
        }

        .modern-table td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
        }

        .modern-table tr:hover {
            background: #f9fafb;
        }

        /* Action Buttons */
        .btn-modern {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }

        .btn-secondary-modern {
            background: #6b7280;
            color: white;
        }

        .btn-secondary-modern:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        /* Loading State */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            color: #6b7280;
        }

        .loading i {
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #d1d5db;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: block;
            }

            .content-area {
                padding: 1rem;
            }
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #374151;
            cursor: pointer;
            padding: 0.5rem;
        }

        /* Report Description */
        .report-description {
            background: #f8fafc;
            padding: 1rem;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary-color);
            margin-bottom: 1.5rem;
            color: #4b5563;
            font-style: italic;
        }

        /* Status Indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            gap: 0.25rem;
        }

        .status-success {
            background: #dcfce7;
            color: #166534;
        }

        .status-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .status-error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Modern Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>
                    <i class="fas fa-chart-line"></i>
                    Analytics Hub
                </h2>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                </div>
            </div>

            <div class="sidebar-section">
                <h3><i class="fas fa-tachometer-alt"></i> Navigation</h3>
                <ul class="sidebar-nav">
                    <li>
                        <a href="systemFunda.php">
                            <i class="fas fa-home"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="#" class="active">
                            <i class="fas fa-chart-bar"></i>
                            Reports & Analytics
                        </a>
                    </li>
                    <li>
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h3><i class="fas fa-file-alt"></i> Report Templates</h3>
                <ul class="sidebar-nav" id="report-templates-list">
                    <li class="loading">
                        <i class="fas fa-spinner"></i>
                        Loading templates...
                    </li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h3><i class="fas fa-bookmark"></i> Saved Reports</h3>
                <ul class="sidebar-nav" id="saved-reports-list">
                    <li class="loading">
                        <i class="fas fa-spinner"></i>
                        Loading saved reports...
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div class="top-bar-content">
                    <div>
                        <button class="mobile-menu-btn" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h1 class="page-title">Reports & Analytics</h1>
                        <div class="breadcrumb">
                            <i class="fas fa-home"></i> Dashboard / Reports & Analytics
                        </div>
                    </div>
                    <div class="user-menu">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-area">
                <div class="modern-card">
                    <div class="card-header-modern">
                        <h5 id="report-title">
                            <i class="fas fa-chart-area"></i>
                            Select a report to view
                        </h5>
                    </div>
                    <div class="card-body-modern">
                        <div id="report-description" class="report-description" style="display: none;"></div>
                        
                        <div class="chart-container">
                            <canvas id="reportChart"></canvas>
                        </div>
                        
                        <div id="report-data-table">
                            <div class="empty-state">
                                <i class="fas fa-chart-pie"></i>
                                <h3>No Report Selected</h3>
                                <p>Choose a report template from the sidebar to get started</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const reportTemplatesList = document.getElementById('report-templates-list');
            const savedReportsList = document.getElementById('saved-reports-list');
            const reportTitle = document.getElementById('report-title');
            const reportDescription = document.getElementById('report-description');
            const reportDataTable = document.getElementById('report-data-table');
            const ctx = document.getElementById('reportChart').getContext('2d');
            let currentChart;
            
            // Fetch report templates
            fetch('api/reports_analytics.php?action=templates')
            .then(response => response.json())
            .then(data => {
                if (data.templates) {
                    renderReportTemplates(data.templates);
                } else {
                    reportTemplatesList.innerHTML = '<li class="empty-state"><i class="fas fa-exclamation-triangle"></i>No templates available</li>';
                }
            })
            .catch(error => {
                console.error('Error fetching report templates:', error);
                reportTemplatesList.innerHTML = '<li class="empty-state"><i class="fas fa-exclamation-triangle"></i>Error loading templates</li>';
            });

            // Fetch saved reports
            fetchSavedReports();

            function fetchSavedReports() {
                fetch('api/reports_analytics.php?action=saved_reports')
                .then(response => response.json())
                .then(data => {
                    if (data.saved_reports) {
                        renderSavedReports(data.saved_reports);
                    } else {
                        savedReportsList.innerHTML = '<li class="empty-state"><i class="fas fa-info-circle"></i>No saved reports</li>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching saved reports:', error);
                    savedReportsList.innerHTML = '<li class="empty-state"><i class="fas fa-exclamation-triangle"></i>Error loading reports</li>';
                });
            }

            function renderReportTemplates(templates) {
                reportTemplatesList.innerHTML = '';
                templates.forEach(template => {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.href = '#';
                    a.innerHTML = `<i class="fas fa-file-chart"></i> ${template.name}`;
                    a.dataset.templateId = template.id;
                    a.addEventListener('click', (e) => {
                        e.preventDefault();
                        // Remove active class from all links
                        document.querySelectorAll('#report-templates-list a').forEach(link => {
                            link.classList.remove('active');
                        });
                        // Add active class to clicked link
                        a.classList.add('active');
                        generateReport(template.id);
                    });
                    li.appendChild(a);
                    reportTemplatesList.appendChild(li);
                });
            }

            function renderSavedReports(reports) {
                savedReportsList.innerHTML = '';
                if (reports.length === 0) {
                    savedReportsList.innerHTML = '<li class="empty-state"><i class="fas fa-info-circle"></i>No saved reports</li>';
                    return;
                }
                reports.forEach(report => {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.href = '#';
                    a.innerHTML = `<i class="fas fa-bookmark"></i> ${report.name}`;
                    a.dataset.reportId = report.id;
                    a.addEventListener('click', (e) => {
                        e.preventDefault();
                        // Remove active class from all links
                        document.querySelectorAll('#saved-reports-list a').forEach(link => {
                            link.classList.remove('active');
                        });
                        // Add active class to clicked link
                        a.classList.add('active');
                        displaySavedReport(report);
                    });
                    li.appendChild(a);
                    savedReportsList.appendChild(li);
                });
            }

            function generateReport(templateId) {
                // Show loading state
                reportDataTable.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i>Generating report...</div>';
                
                fetch(`api/reports_analytics.php?action=generate_report&template_id=${templateId}`)
                .then(response => response.json())
                .then(result => {
                    if (result.report && result.report.data) {
                        displayReport(result.report);
                    } else {
                        console.error('Error generating report:', result.error);
                        reportDataTable.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error</h3><p>Error generating report: ' + (result.error || 'Unknown error') + '</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error generating report:', error);
                    reportDataTable.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error</h3><p>Failed to generate report. Please try again.</p></div>';
                });
            }

            function displayReport(report) {
                reportTitle.innerHTML = `<i class="fas fa-chart-area"></i> ${report.template.name}`;
                if (report.template.description) {
                    reportDescription.textContent = report.template.description;
                    reportDescription.style.display = 'block';
                } else {
                    reportDescription.style.display = 'none';
                }
                
                renderChart(report.template.chart_type, report.data, report.template.name);
                renderTable(report.data);
            }

            function displaySavedReport(report) {
                reportTitle.innerHTML = `<i class="fas fa-bookmark"></i> ${report.name}`;
                if (report.description) {
                    reportDescription.textContent = report.description;
                    reportDescription.style.display = 'block';
                } else {
                    reportDescription.style.display = 'none';
                }
                
                const reportData = JSON.parse(report.report_data);
                renderChart(reportData.template.chart_type, reportData.data, reportData.template.name);
                renderTable(reportData.data);
            }

            function renderChart(type, data, label) {
                if (currentChart) {
                    currentChart.destroy();
                }

                if(data.length === 0) {
                    reportDataTable.innerHTML = '<div class="empty-state"><i class="fas fa-chart-pie"></i><h3>No Data</h3><p>No data available for this report.</p></div>';
                    return;
                }
                
                const labels = data.map(item => Object.values(item)[0]);
                const values = data.map(item => Object.values(item)[1]);
                
                const chartConfig = {
                    type: type || 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: label,
                            data: values,
                            backgroundColor: [
                                'rgba(37, 99, 235, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(147, 197, 253, 0.8)',
                                'rgba(96, 165, 250, 0.8)',
                                'rgba(129, 140, 248, 0.8)',
                                'rgba(139, 92, 246, 0.8)'
                            ],
                            borderColor: [
                                'rgba(37, 99, 235, 1)',
                                'rgba(59, 130, 246, 1)',
                                'rgba(147, 197, 253, 1)',
                                'rgba(96, 165, 250, 1)',
                                'rgba(129, 140, 248, 1)',
                                'rgba(139, 92, 246, 1)'
                            ],
                            borderWidth: 2,
                            borderRadius: 6,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20,
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: 'white',
                                bodyColor: 'white',
                                borderColor: 'rgba(37, 99, 235, 1)',
                                borderWidth: 1,
                                cornerRadius: 8,
                                displayColors: false,
                                callbacks: {
                                    title: function(tooltipItems) {
                                        // Show full title in tooltip
                                        return tooltipItems[0].label;
                                    },
                                    label: function(context) {
                                        return 'Progress: ' + context.parsed.y + '%';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)',
                                    drawBorder: false
                                },
                                ticks: {
                                    color: '#6b7280',
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#6b7280',
                                    font: {
                                        size: 10
                                    },
                                    maxRotation: 45,
                                    minRotation: 45,
                                    callback: function(value, index, ticks) {
                                        const label = this.getLabelForValue(value);
                                        // Truncate long labels and add ellipsis
                                        if (label && label.length > 20) {
                                            return label.substring(0, 20) + '...';
                                        }
                                        return label;
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Thesis Projects',
                                    color: '#6b7280',
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                }
                            }
                        }
                    }
                };
                
                currentChart = new Chart(ctx, chartConfig);
            }

            function renderTable(data) {
                if(data.length === 0) {
                    reportDataTable.innerHTML = '<div class="empty-state"><i class="fas fa-table"></i><h3>No Data</h3><p>No data available for this report.</p></div>';
                    return;
                }
                
                // Create table element
                let table = document.createElement('table');
                table.className = 'modern-table';
                
                // Create table header
                let thead = document.createElement('thead');
                let headerRow = document.createElement('tr');
                
                // Get column names from first data item
                const columns = Object.keys(data[0]);
                
                columns.forEach(column => {
                    let th = document.createElement('th');
                    // Improved column header formatting
                    let headerText;
                    switch(column.toLowerCase()) {
                        case 'thesis_info':
                            headerText = 'Student & Thesis Title';
                            break;
                        case 'progress_percentage':
                            headerText = 'Progress (%)';
                            break;
                        case 'student_name':
                            headerText = 'Student';
                            break;
                        case 'adviser_name':
                            headerText = 'Adviser';
                            break;
                        case 'submission_count':
                            headerText = 'Submissions';
                            break;
                        case 'student_count':
                            headerText = 'Students';
                            break;
                        case 'completion_rate':
                            headerText = 'Completion Rate (%)';
                            break;
                        case 'chapter_name':
                            headerText = 'Chapter';
                            break;
                        case 'submitted_count':
                            headerText = 'Submitted';
                            break;
                        case 'total_chapters':
                            headerText = 'Total';
                            break;
                        case 'approval_rate':
                            headerText = 'Approval Rate (%)';
                            break;
                        case 'chapter_1':
                            headerText = 'Ch. 1';
                            break;
                        case 'chapter_2':
                            headerText = 'Ch. 2';
                            break;
                        case 'chapter_3':
                            headerText = 'Ch. 3';
                            break;
                        case 'chapter_4':
                            headerText = 'Ch. 4';
                            break;
                        case 'chapter_5':
                            headerText = 'Ch. 5';
                            break;
                        case 'completed_theses':
                            headerText = 'Completed';
                            break;
                        case 'total_students':
                            headerText = 'Students';
                            break;
                        case 'avg_progress':
                            headerText = 'Avg Progress (%)';
                            break;
                        case 'month':
                            headerText = 'Month';
                            break;
                        case 'submissions':
                            headerText = 'Submissions';
                            break;
                        case 'active_students':
                            headerText = 'Active Students';
                            break;
                        case 'status_name':
                            headerText = 'Status';
                            break;
                        case 'count':
                            headerText = 'Count';
                            break;
                        case 'approved_chapters':
                            headerText = 'Approved Chapters';
                            break;
                        case 'submitted':
                            headerText = 'Submitted';
                            break;
                        case 'approved':
                            headerText = 'Approved';
                            break;
                        case 'department':
                            headerText = 'Department';
                            break;
                        case 'total_theses':
                            headerText = 'Total Theses';
                            break;
                        case 'completed':
                            headerText = 'Completed';
                            break;
                        case 'status':
                            headerText = 'Status';
                            break;
                        default:
                            headerText = column.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    }
                    th.textContent = headerText;
                    headerRow.appendChild(th);
                });
                
                thead.appendChild(headerRow);
                table.appendChild(thead);
                
                // Create table body
                let tbody = document.createElement('tbody');
                
                data.forEach(item => {
                    let row = document.createElement('tr');
                    
                    columns.forEach(column => {
                        let cell = document.createElement('td');
                        let cellValue = item[column];
                        
                        // Format cell content based on column type
                        switch(column.toLowerCase()) {
                            case 'thesis_info':
                                // Split student name and thesis title for better readability
                                if (cellValue && cellValue.includes(' - ')) {
                                    const parts = cellValue.split(' - ');
                                    const studentName = parts[0];
                                    const thesisTitle = parts.slice(1).join(' - ');
                                    cell.innerHTML = `<strong>${studentName}</strong><br><small class="text-gray-600">${thesisTitle}</small>`;
                                } else {
                                    cell.textContent = cellValue;
                                }
                                break;
                            case 'progress_percentage':
                                // Add percentage symbol and color coding
                                const progress = parseFloat(cellValue);
                                let progressClass = '';
                                if (progress >= 80) progressClass = 'text-green-600 font-semibold';
                                else if (progress >= 50) progressClass = 'text-blue-600 font-medium';
                                else if (progress >= 25) progressClass = 'text-yellow-600 font-medium';
                                else progressClass = 'text-red-600 font-medium';
                                
                                cell.innerHTML = `<span class="${progressClass}">${progress}%</span>`;
                                break;
                            case 'completion_rate':
                                // Format completion rate with percentage
                                const rate = parseFloat(cellValue);
                                cell.innerHTML = `<span class="font-medium">${rate.toFixed(1)}%</span>`;
                                break;
                            case 'student_count':
                            case 'submission_count':
                            case 'submitted_count':
                            case 'total_chapters':
                            case 'submitted':
                            case 'approved':
                            case 'total_students':
                            case 'completed_theses':
                            case 'completed':
                            case 'submissions':
                            case 'active_students':
                            case 'total_theses':
                                // Add styling for counts
                                cell.innerHTML = `<span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium">${cellValue}</span>`;
                                break;
                            case 'chapter_1':
                            case 'chapter_2':
                            case 'chapter_3':
                            case 'chapter_4':
                            case 'chapter_5':
                                // Chapter completion indicators
                                const chapterStatus = parseInt(cellValue) > 0 ? 'completed' : 'pending';
                                const chapterClass = chapterStatus === 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600';
                                const chapterIcon = chapterStatus === 'completed' ? '✓' : '○';
                                cell.innerHTML = `<span class="${chapterClass} px-2 py-1 rounded-full text-sm font-medium">${chapterIcon}</span>`;
                                break;
                            case 'approval_rate':
                            case 'avg_progress':
                                // Format rates and averages with color coding
                                const value = parseFloat(cellValue);
                                let rateClass = '';
                                if (value >= 80) rateClass = 'text-green-600 font-semibold';
                                else if (value >= 60) rateClass = 'text-blue-600 font-medium';
                                else if (value >= 40) rateClass = 'text-yellow-600 font-medium';
                                else rateClass = 'text-red-600 font-medium';
                                
                                cell.innerHTML = `<span class="${rateClass}">${value}%</span>`;
                                break;
                            case 'status':
                                // Format thesis status
                                const statusColors = {
                                    'draft': 'bg-gray-100 text-gray-800',
                                    'in_progress': 'bg-blue-100 text-blue-800',
                                    'submitted': 'bg-yellow-100 text-yellow-800',
                                    'approved': 'bg-green-100 text-green-800',
                                    'rejected': 'bg-red-100 text-red-800'
                                };
                                const statusColor = statusColors[cellValue] || 'bg-gray-100 text-gray-800';
                                cell.innerHTML = `<span class="px-2 py-1 rounded-full text-xs font-medium ${statusColor}">${cellValue.charAt(0).toUpperCase() + cellValue.slice(1)}</span>`;
                                break;
                            default:
                                cell.textContent = cellValue;
                        }
                        
                        row.appendChild(cell);
                    });
                    
                    tbody.appendChild(row);
                });
                
                table.appendChild(tbody);
                
                // Create button container
                const buttonContainer = document.createElement('div');
                buttonContainer.className = 'mt-4 flex gap-2';

                // Add save button
                let saveButton = document.createElement('button');
                saveButton.className = 'btn-modern btn-primary-modern';
                saveButton.innerHTML = '<i class="fas fa-save"></i> Save Report';
                saveButton.addEventListener('click', () => {
                    saveCurrentReport(data);
                });
                buttonContainer.appendChild(saveButton);

                // Add download button for saved reports
                const currentReportId = document.querySelector('#saved-reports-list a.active')?.dataset.reportId;
                if (currentReportId) {
                    const downloadButton = document.createElement('a');
                    downloadButton.href = `api/download_report.php?report_id=${currentReportId}`;
                    downloadButton.className = 'btn-modern btn-success-modern';
                    downloadButton.innerHTML = '<i class="fas fa-download"></i> Download CSV';
                    buttonContainer.appendChild(downloadButton);
                }
                
                // Clear previous content and add new table
                reportDataTable.innerHTML = '';
                reportDataTable.appendChild(table);
                reportDataTable.appendChild(document.createElement('br'));
                reportDataTable.appendChild(buttonContainer);
            }
            
            function saveCurrentReport(data) {
                const reportName = prompt('Enter a name for this report:');
                if (!reportName) return;
                
                const reportDescription = prompt('Enter a description (optional):');
                
                const reportData = {
                    template: {
                        name: reportTitle.textContent.replace(/.*? /, ''), // Remove icon
                        description: reportDescription || '',
                        chart_type: currentChart ? currentChart.config.type : 'bar'
                    },
                    data: data
                };
                
                fetch('api/reports_analytics.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'save_report',
                        template_id: document.querySelector('#report-templates-list a.active')?.dataset.templateId || 1,
                        name: reportName,
                        description: reportDescription || '',
                        report_data: reportData,
                        parameters_used: {}
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('✅ Report saved successfully!');
                        fetchSavedReports(); // Refresh the saved reports list
                    } else {
                        alert('❌ Error saving report: ' + (result.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error saving report:', error);
                    alert('❌ Error saving report. Please try again.');
                });
            }
        });

        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !mobileMenuBtn.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 