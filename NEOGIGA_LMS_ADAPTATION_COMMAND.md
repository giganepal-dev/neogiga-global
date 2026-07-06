# Implement LMS from SkillGro/eLMS references

Use this in a future implementation phase. Do not run destructive commands. First read `NEOGIGA_LMS_REFERENCE_MAP.md` and `NEOGIGA_REFERENCE_LICENSE_SECURITY_REVIEW.md`.

## Source Files To Inspect First

### SkillGro Course LMS Laravel Script
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Course/app/Http/Requests/ChapterLessonRequest.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/lang/en.json`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/resources/views/frontend/student-dashboard/enrolled-courses/index.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Assignment/app/Http/Controllers/StudentAssignmentController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Assignment/app/Http/Controllers/InstructorAssignmentController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Assignment/app/Services/AssignmentService.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/app/Http/Controllers/Frontend/StudentDashboardController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/app/Http/Resources/API/CourseDetailsCollection.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/database/seeders/CourseSeeder.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Course/resources/views/course/partials/lesson-create-modal.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/Modules/Course/resources/views/course/partials/lesson-edit-modal.blade.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/app/Http/Controllers/API/DashboardController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/app/Http/Controllers/Frontend/LearningController.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/app/Http/Requests/Frontend/ChapterLessonRequest.php`
- `extracted/skillgro-340/codecanyon-53608520-skillgro-course-learning-management-system-laravel-script-lms/main_files/resources/views/frontend/instructor-dashboard/course/partials/lesson-create-modal.blade.php`

### eLMS Online Learning Management System
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/components/instructor/addCourse/forms/LectureForm.tsx`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/components/instructor/addCourse/AddCourseContent.tsx`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/components/instructor/addCourse/Tabs/CurriculumTab.tsx`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/components/instructor/courses/StudentEnrolled.tsx`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/api/instructor/course/getCourseDetails.ts`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/api/instructor/course/getCourseEnrollStudents.ts`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/api/user/getCourse.ts`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/locale/en.json`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/assets/languages/en_ar.json`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/assets/languages/template.json`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/core/constants/app_labels.dart`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-App-V1.1.2/eLMS-App-V1.1.2/lib/features/course/widgets/chapter_expansion_tile_widget.dart`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/components/instructor/addCourse/forms/AssignmentForm.tsx`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/api/instructor/course/getCurriculumDetails.ts`
- `extracted/elms-112/codecanyon-60942644-elms-online-learning-management-system-lms-flutter-app-with-laravel-admin-panel-nextjs-web/eLMS-Web-v1.1.2/e-lms-web-v1.1.2/src/utils/api/instructor/createCourseApis/create-course/createCurriculum.ts`

### ERPGo SaaS ERP CRM POS
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Taskly/src/Database/Seeders/DemoProjectBugSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Training/src/Database/Seeders/TrainingFeedbackDemoSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Training/src/Database/Seeders/TrainingTaskDemoSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/SupportTicket/src/Database/Seeders/DemoKnowledgeBaseSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Contract/src/Database/Seeders/DemoContractSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Lead/src/Database/Seeders/DemoDealEmailDiscussionSeeder.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Recruitment/src/Resources/lang/it.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/SupportTicket/src/Resources/lang/fr.json`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Webhook/src/Extractors/CourseDataExtractor.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Webhook/src/Extractors/CourseOrderDataExtractor.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/packages/workdo/Webhook/src/Extractors/VideoDataExtractor.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Classes/Module.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Http/Controllers/ModuleController.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Http/Middleware/PlanModuleCheck.php`
- `extracted/erpgosaas-95/codecanyon-33263426-erpgo-saas-all-in-one-business-erp-with-project-account-hrm-crm-pos/main-file/app/Http/Requests/UpdateModulePriceRequest.php`

## Implementation Command

```text
Audit the current NeoGiga Laravel app first. Then implement LMS as an additive integration layer only. Reuse existing routes, models, services, migrations, admin UI, and data where present. Do not delete or overwrite existing code/data. Create a restore point before migrations. Rewrite reference logic into NeoGiga namespaces and PostgreSQL-safe incremental migrations. Do not copy .env, SQL dumps, credentials, vendor/node_modules, or nulled code. Add request validation, policies/admin guards, API resources, service classes, audit logs for admin writes, docs, and focused tests where the app supports tests. Update CHANGELOG.md.
```

## Required NeoGiga Work Items

- Add lms_* migrations for courses/categories/modules/lessons/enrollments/progress/quizzes/assignments/certificates/product links.
- Add App\Models\Lms models and API controllers/resources.
- Add CourseCatalogService, EnrollmentService, ProgressTrackingService, CertificateIssueService.
- Add admin dashboard pages and SEO public endpoints.
- Add tests for enrollment, progress, certificate rules.

## Safety Checklist

- Backup before migrations/imports.
- Incremental migrations only.
- No raw SQL import.
- No secret copying.
- No real provider sending/payment behavior unless explicitly enabled.
- Update docs and CHANGELOG.md.
