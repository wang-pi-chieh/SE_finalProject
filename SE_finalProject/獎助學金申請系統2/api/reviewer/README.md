# Reviewer API Module

Role-specific reviewer endpoints live here to avoid growing the crowded legacy `api/` root.

| Endpoint | Purpose |
| --- | --- |
| `get_disbursements.php` | List approved applications that need award disbursement tracking |
| `update_disbursement.php` | Update award payout status and create student notifications |
| `get_review_results.php` | Return review stages, stage scores, and integrated review scores |
| `get_final_award_list.php` | Generate the final selected/waitlisted award list by quota and score |
| `confirm_final_award_list.php` | Persist the currently generated final award list into `final_award_results` |
