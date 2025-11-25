<?php
include 'includes/conn.php';
include 'includes/functions.php';
requireRole('student');

$student_id = $_SESSION['user_id'];

// Get student's grades
$grades_stmt = $pdo->prepare("
    SELECT g.*, s.name as subject_name, c.name as classroom_name, 
           u.first_name as teacher_first, u.last_name as teacher_last 
    FROM grades g 
    JOIN subjects s ON g.subject_id = s.id 
    JOIN classrooms c ON g.classroom_id = c.id 
    JOIN users u ON g.teacher_id = u.id 
    WHERE g.student_id = ? 
    ORDER BY g.graded_at DESC
");
$grades_stmt->execute([$student_id]);
$grades = $grades_stmt->fetchAll();

// Calculate statistics
$total_grades = count($grades);
$average_grade = 0;
if ($total_grades > 0) {
    $sum = 0;
    foreach ($grades as $grade) {
        $sum += $grade['grade'];
    }
    $average_grade = $sum / $total_grades;
}

// Get grade distribution
$grade_distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
foreach ($grades as $grade) {
    $letter_grade = calculateGradePoint($grade['grade']);
    $grade_distribution[$letter_grade]++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Grade Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            min-height: 100vh;
            padding: 0;
        }
        .sidebar .nav-link {
            color: white;
            padding: 1rem 1.5rem;
            border-left: 4px solid transparent;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            border-left-color: white;
        }
        .grade-progress {
            height: 10px;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="d-flex flex-column p-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-graduate fa-2x mb-2"></i>
                        <h5>Student Portal</h5>
                        <small><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></small>
                    </div>
                    
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#dashboard" data-bs-toggle="tab">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#grades" data-bs-toggle="tab">
                                <i class="fas fa-chart-bar me-2"></i>My Grades
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#performance" data-bs-toggle="tab">
                                <i class="fas fa-trending-up me-2"></i>Performance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="tab-content">
                    <!-- Dashboard Tab -->
                    <div class="tab-pane fade show active" id="dashboard">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Student Dashboard</h2>
                            
                        </div>

                        <!-- Statistics -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card stat-card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4><?php echo $total_grades; ?></h4>
                                                <p>Total Grades</p>
                                            </div>
                                            <i class="fas fa-list-alt fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4><?php echo number_format($average_grade, 2); ?></h4>
                                                <p>Average Grade</p>
                                            </div>
                                            <i class="fas fa-calculator fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card bg-warning text-dark">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4><?php echo calculateGradePoint($average_grade); ?></h4>
                                                <p>Overall Grade</p>
                                            </div>
                                            <i class="fas fa-award fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card bg-info text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4><?php echo $grade_distribution['A']; ?></h4>
                                                <p>A Grades</p>
                                            </div>
                                            <i class="fas fa-star fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grade Distribution -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Grade Distribution</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach($grade_distribution as $letter => $count): 
                                            $percentage = $total_grades > 0 ? ($count / $total_grades) * 100 : 0;
                                            $color_class = '';
                                            switch($letter) {
                                                case 'A': $color_class = 'bg-success'; break;
                                                case 'B': $color_class = 'bg-info'; break;
                                                case 'C': $color_class = 'bg-warning'; break;
                                                case 'D': $color_class = 'bg-danger'; break;
                                                case 'F': $color_class = 'bg-dark'; break;
                                            }
                                        ?>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Grade <?php echo $letter; ?></span>
                                                <span><?php echo $count; ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                                            </div>
                                            <div class="progress grade-progress">
                                                <div class="progress-bar <?php echo $color_class; ?>" 
                                                     style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Recent Grades</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach(array_slice($grades, 0, 5) as $grade): 
                                            $grade_color = getGradeColor($grade['grade']);
                                        ?>
                                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                                            <div>
                                                <h6 class="mb-0"><?php echo $grade['subject_name']; ?></h6>
                                                <small class="text-muted"><?php echo $grade['classroom_name']; ?></small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-<?php echo $grade_color; ?>">
                                                    <?php echo $grade['grade']; ?>
                                                </span>
                                                <div>
                                                    <small class="text-muted"><?php echo date('M j', strtotime($grade['graded_at'])); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grades Tab -->
                    <div class="tab-pane fade" id="grades">
                        <h2 class="mb-4">My Grades</h2>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5>All Grades</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Classroom</th>
                                                <th>Grade</th>
                                                <th>Letter</th>
                                                <th>Type</th>
                                                <th>Teacher</th>
                                                <th>Remarks</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($grades as $grade): 
                                                $letter_grade = calculateGradePoint($grade['grade']);
                                                $grade_color = getGradeColor($grade['grade']);
                                            ?>
                                            <tr>
                                                <td><?php echo $grade['subject_name']; ?></td>
                                                <td><?php echo $grade['classroom_name']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $grade_color; ?>">
                                                        <?php echo $grade['grade']; ?>
                                                    </span>
                                                </td>
                                                <td><strong><?php echo $letter_grade; ?></strong></td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo ucfirst($grade['grade_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $grade['teacher_first'] . ' ' . $grade['teacher_last']; ?></td>
                                                <td><?php echo $grade['remarks']; ?></td>
                                                <td><?php echo date('M j, Y', strtotime($grade['graded_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Tab -->
                    <div class="tab-pane fade" id="performance">
                        <h2 class="mb-4">Performance Analysis</h2>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Grade Trend by Subject</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="gradeChart" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Grade Summary</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center">
                                            <h1 class="display-4 text-<?php 
                                                echo getGradeColor($average_grade); 
                                            ?>"><?php echo number_format($average_grade, 1); ?></h1>
                                            <p class="lead">Overall Average</p>
                                            <h3><?php echo calculateGradePoint($average_grade); ?></h3>
                                        </div>
                                        <hr>
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <h5><?php echo $total_grades; ?></h5>
                                                <small>Total Records</small>
                                            </div>
                                            <div class="col-6">
                                                <h5><?php echo count(array_unique(array_column($grades, 'subject_id'))); ?></h5>
                                                <small>Subjects</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var triggerTabList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="tab"]'))
            triggerTabList.forEach(function (triggerEl) {
                var tabTrigger = new bootstrap.Tab(triggerEl)
                triggerEl.addEventListener('click', function (event) {
                    event.preventDefault()
                    tabTrigger.show()
                })
            });

            // Simple grade chart
            var ctx = document.getElementById('gradeChart').getContext('2d');
            var gradeChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Quiz 1', 'Assignment 1', 'Exam 1', 'Quiz 2', 'Project'],
                    datasets: [{
                        label: 'Grades',
                        data: [85, 92, 78, 88, 95],
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 50,
                            max: 100
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>