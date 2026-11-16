<?php
require_once 'Database.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class Job extends Database {

    private function checkExpirations() {
        date_default_timezone_set('Asia/Manila');
        $today = date('Y-m-d');
        
        $sql = "SELECT id FROM job_posts WHERE expiration_date < ? AND status = 'Active'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $jobId = $row['id'];
            $this->conn->query("UPDATE job_posts SET status = 'Expired' WHERE id = $jobId");
            $this->conn->query("UPDATE job_applications SET status = 'Rejected' WHERE job_id = $jobId AND status = 'Pending'");
        }
    }

    public function createJob($data) {
        $title = $data['job_title'] ?? '';
        $company = $data['company_name'] ?? '';
        $description = $data['job_description'] ?? '';
        $minSalary = $data['min_salary'] ?? 0;
        $maxSalary = $data['max_salary'] ?? 0;
        $salaryPeriod = $data['salary_period'] ?? 'Monthly';
        $jobType = $data['job_type'] ?? 'Full-Time';
        $location = $data['location'] ?? '';
        $requirements = $data['requirements'] ?? '';
        
        $expirationDate = !empty($data['expiration_date']) ? $data['expiration_date'] : date('Y-m-d', strtotime('+7 days'));
        $postedBy = $data['posted_by'] ?? 0;

        $sql = "INSERT INTO job_posts (job_title, company_name, job_description, min_salary, max_salary, salary_period, job_type, location, requirements, expiration_date, status, posted_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssdddssssi", $title, $company, $description, $minSalary, $maxSalary, $salaryPeriod, $jobType, $location, $requirements, $expirationDate, $postedBy);
        
        if ($stmt->execute()) {
            return json_encode(['success' => true, 'message' => 'Job posted successfully!']);
        }
        return json_encode(['success' => false, 'message' => 'Failed to post job.']);
    }

    public function getAllJobs($role = 'Applicant', $userId = 0) {
        $this->checkExpirations();

        if ($role === 'Employer' && $userId > 0) {
            $sql = "SELECT j.*, u.email as employer_email 
                    FROM job_posts j 
                    LEFT JOIN users u ON j.posted_by = u.id 
                    WHERE j.posted_by = ? 
                    ORDER BY j.created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql = "SELECT j.*, u.email as employer_email 
                    FROM job_posts j 
                    LEFT JOIN users u ON j.posted_by = u.id 
                    WHERE j.status = 'Active' 
                    ORDER BY j.created_at DESC";
            $result = $this->conn->query($sql);
        }

        $jobs = [];
        while ($row = $result->fetch_assoc()) {
            $jobs[] = $row;
        }
        return json_encode(['success' => true, 'data' => $jobs]);
    }

    public function updateJob($data) {
        $jobId = $data['job_id'] ?? 0;
        $title = $data['job_title'] ?? '';
        $company = $data['company_name'] ?? '';
        $description = $data['job_description'] ?? '';
        $minSalary = $data['min_salary'] ?? 0;
        $maxSalary = $data['max_salary'] ?? 0;
        $salaryPeriod = $data['salary_period'] ?? 'Monthly';
        $jobType = $data['job_type'] ?? 'Full-Time';
        $location = $data['location'] ?? '';
        $requirements = $data['requirements'] ?? '';
        $expirationDate = !empty($data['expiration_date']) ? $data['expiration_date'] : date('Y-m-d');

        $sql = "UPDATE job_posts SET job_title = ?, company_name = ?, job_description = ?, min_salary = ?, max_salary = ?, salary_period = ?, job_type = ?, location = ?, requirements = ?, expiration_date = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssddsssssi", $title, $company, $description, $minSalary, $maxSalary, $salaryPeriod, $jobType, $location, $requirements, $expirationDate, $jobId);
        
        if ($stmt->execute()) {
            return json_encode(['success' => true, 'message' => 'Job updated successfully!']);
        }
        return json_encode(['success' => false, 'message' => 'Failed to update job.']);
    }

    public function deleteJob($jobId) {
        $this->conn->query("DELETE FROM job_applications WHERE job_id = " . intval($jobId));
        
        $sql = "DELETE FROM job_posts WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $jobId);
        
        if ($stmt->execute()) {
            return json_encode(['success' => true, 'message' => 'Job deleted successfully!']);
        }
        return json_encode(['success' => false, 'message' => 'Failed to delete job.']);
    }

    public function applyJob($jobId, $applicantId) {
        $check = $this->conn->prepare("SELECT id FROM job_applications WHERE job_id = ? AND applicant_id = ?");
        $check->bind_param("ii", $jobId, $applicantId);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return json_encode(['success' => false, 'message' => 'You have already applied for this job!']);
        }

        $sql = "INSERT INTO job_applications (job_id, applicant_id, status) VALUES (?, ?, 'Pending')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $jobId, $applicantId);
        
        if ($stmt->execute()) {
            return json_encode(['success' => true, 'message' => 'Application submitted successfully!']);
        }
        return json_encode(['success' => false, 'message' => 'Failed to submit application.']);
    }
}
?>