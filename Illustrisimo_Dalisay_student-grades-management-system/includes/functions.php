<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function requireRole($role) {
    if (!isLoggedIn() || !hasRole($role)) {
        redirect('index.php');
    }
}

function getDashboardUrl() {
    if (!isLoggedIn()) return 'index.php';
    
    switch ($_SESSION['role']) {
        case 'admin': return 'admin.php';
        case 'teacher': return 'teacher.php';
        case 'student': return 'students.php';
        default: return 'index.php';
    }
}

function calculateGradePoint($grade) {
    if ($grade >= 90) return 'A';
    if ($grade >= 80) return 'B';
    if ($grade >= 70) return 'C';
    if ($grade >= 60) return 'D';
    return 'F';
}

function getGradeColor($grade) {
    if ($grade >= 90) return 'success';
    if ($grade >= 80) return 'info';
    if ($grade >= 70) return 'warning';
    return 'danger';
}

function deleteUser($pdo, $user_id) {
    // Don't allow users to delete themselves
    if ($user_id == $_SESSION['user_id']) {
        return "You cannot delete your own account!";
    }
    
    try {
        $pdo->beginTransaction();
        
        // Delete user's grades first
        $stmt = $pdo->prepare("DELETE FROM grades WHERE student_id = ? OR teacher_id = ?");
        $stmt->execute([$user_id, $user_id]);
        
        // Delete from classroom_students
        $stmt = $pdo->prepare("DELETE FROM classroom_students WHERE student_id = ?");
        $stmt->execute([$user_id]);
        
        // Update classrooms if user is a teacher
        $stmt = $pdo->prepare("UPDATE classrooms SET teacher_id = NULL WHERE teacher_id = ?");
        $stmt->execute([$user_id]);
        
        // Finally delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return "Error deleting user: " . $e->getMessage();
    }
}

function deleteSubject($pdo, $subject_id) {
    try {
        $pdo->beginTransaction();
        
        // Delete grades associated with this subject
        $stmt = $pdo->prepare("DELETE FROM grades WHERE subject_id = ?");
        $stmt->execute([$subject_id]);
        
        // Delete the subject
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return "Error deleting subject: " . $e->getMessage();
    }
}

function deleteClassroom($pdo, $classroom_id) {
    try {
        $pdo->beginTransaction();
        
        // Delete grades associated with this classroom
        $stmt = $pdo->prepare("DELETE FROM grades WHERE classroom_id = ?");
        $stmt->execute([$classroom_id]);
        
        // Delete classroom students
        $stmt = $pdo->prepare("DELETE FROM classroom_students WHERE classroom_id = ?");
        $stmt->execute([$classroom_id]);
        
        // Delete the classroom
        $stmt = $pdo->prepare("DELETE FROM classrooms WHERE id = ?");
        $stmt->execute([$classroom_id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return "Error deleting classroom: " . $e->getMessage();
    }
}

function deleteGrade($pdo, $grade_id, $teacher_id) {
    // Verify the grade belongs to the teacher
    $stmt = $pdo->prepare("SELECT * FROM grades WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$grade_id, $teacher_id]);
    
    if ($stmt->rowCount() === 0) {
        return "Grade not found or you don't have permission to delete it!";
    }
    
    $stmt = $pdo->prepare("DELETE FROM grades WHERE id = ?");
    $stmt->execute([$grade_id]);
    return true;
}

function removeStudentFromClassroom($pdo, $classroom_id, $student_id) {
    try {
        $pdo->beginTransaction();
        
        // Delete grades for this student in this classroom
        $stmt = $pdo->prepare("DELETE FROM grades WHERE classroom_id = ? AND student_id = ?");
        $stmt->execute([$classroom_id, $student_id]);
        
        // Remove student from classroom
        $stmt = $pdo->prepare("DELETE FROM classroom_students WHERE classroom_id = ? AND student_id = ?");
        $stmt->execute([$classroom_id, $student_id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return "Error removing student: " . $e->getMessage();
    }
}

function getClassroomStudents($pdo, $classroom_id) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.username, u.email 
        FROM users u 
        JOIN classroom_students cs ON u.id = cs.student_id 
        WHERE cs.classroom_id = ? 
        ORDER BY u.first_name, u.last_name
    ");
    $stmt->execute([$classroom_id]);
    return $stmt->fetchAll();
}
?>