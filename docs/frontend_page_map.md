# Frontend Page Map

## Core Pages

| Route Path | Component File | Main Subcomponents | Key UI Actions | Expected API Endpoints | WebSocket Events |
|------------|---------------|-------------------|----------------|----------------------|------------------|
| / | Pages/Landing.vue | Hero, Features, CTA | Navigate to login/register | None | None |
| /login | Pages/Auth/Login.vue | LoginForm | Login | POST /login | None |
| /register | Pages/Auth/Register.vue | RegisterForm | Register | POST /register | None |
| /forgot-password | Pages/Auth/ForgotPassword.vue | EmailForm | Request reset | POST /forgot-password | None |
| /reset-password | Pages/Auth/ResetPassword.vue | PasswordResetForm | Reset password | POST /reset-password | None |
| /dashboard | Pages/Dashboard.vue | StatsCards, Charts, RecentAlerts | View overview, navigate | GET /api/dashboard/stats, /charts, /recent-alerts | alert.created |
| /network-logs | Pages/NetworkLogs/Index.vue | DataTable, Filters, Pagination | List, filter, search | GET /network-logs | None |
| /network-logs/create | Pages/NetworkLogs/Create.vue | FileUpload, ProgressBar | Upload PCAP | POST /network-logs | pcap.processing |
| /network-logs/:id | Pages/NetworkLogs/Show.vue | LogDetails, PacketViewer | View details | GET /network-logs/:id | None |
| /alerts | Pages/Alerts/Index.vue | AlertTable, SeverityFilter | List, filter, acknowledge | GET /alerts | alert.created, alert.updated |
| /alerts/:id | Pages/Alerts/Show.vue | AlertDetails, Evidence | View details, investigate | GET /alerts/:id | alert.updated |
| /alerts/create | Pages/Alerts/Create.vue | AlertForm | Manual alert creation | POST /alerts | None |
| /live-monitoring | Pages/NetworkAnalysis/LiveMonitoring.vue | InterfaceSelector, PacketCapture, RealTimeChart | Start/stop capture, view packets | POST /api/live-monitoring/start, /stop, GET /poll | packet.captured |
| /ml-models | Pages/MLModels/Index.vue | ModelList, StatusCards | List models, activate | GET /ml-models | model.status |
| /ml-models/create | Pages/MLModels/Create.vue | ModelForm, ConfigEditor | Create model | POST /ml-models | None |
| /ml-models/:id | Pages/MLModels/Show.vue | ModelDetails, MetricsChart | View details, metrics | GET /ml-models/:id, GET /api/ml-models/:id/metrics | None |
| /ml-models/:id/train | Pages/MLModels/Train.vue | TrainingConfig, ProgressBar | Configure & start training | POST /api/ml-models/:id/start-training | training.progress |
| /rules | Pages/Rules/Index.vue | RuleTable, FilterBar | List, toggle, filter | GET /rules | rule.updated |
| /rules/create | Pages/Rules/Create.vue | RuleEditor | Create rule | POST /rules | None |
| /rules/:id | Pages/Rules/Show.vue | RuleDetails, TestRunner | View, test rule | GET /rules/:id | None |
| /investigations | Pages/Investigations/Index.vue | InvestigationList | List investigations | GET /investigations | None |
| /investigations/create | Pages/Investigations/Create.vue | InvestigationForm | Create investigation | POST /investigations | None |
| /investigations/:id | Pages/Investigations/Show.vue | Timeline, AlertCorrelation | View investigation | GET /investigations/:id | investigation.updated |
| /analytics | Pages/Analytics.vue | Charts, DateRangePicker, ExportButton | View analytics, export | GET /analytics | None |
| /notifications | Pages/Notifications/Index.vue | NotificationList | View, mark read | GET /api/notifications | notification.new |
| /users | Pages/Users/Index.vue | UserTable, RoleManager | Manage users | GET /users | None |
| /settings | Pages/Settings/Index.vue | SettingsTabs, ConfigForms | Update settings | GET /settings, PUT /settings/* | None |
| /system-health | Pages/SystemHealth/Index.vue | MetricsCards, ServiceStatus | Monitor health | GET /system-health | health.update |
| /profile | Pages/Profile/Show.vue | ProfileInfo, ActivityLog | View profile | GET /profile | None |
| /profile/edit | Pages/Profile/Edit.vue | ProfileForm, PasswordForm | Update profile | PATCH /profile, PUT /password | None |

## Component Dependencies

### Layout Components
- AuthenticatedLayout.vue - Main app layout with sidebar
- GuestLayout.vue - Layout for auth pages

### Shared Components
- Components/Charts/BarChart.vue
- Components/Charts/LineChart.vue
- Components/Charts/PieChart.vue
- Components/DataTable.vue
- Components/SearchBox.vue
- Components/FilterBar.vue
- Components/Modal.vue
- Components/Dropdown.vue
- Components/ToastContainer.vue
- Components/SkeletonLoader.vue
- Components/EmptyState.vue

## Key WebSocket Channels
- `network.live.{userId}` - Live monitoring packets
- `alerts.{userId}` - Alert notifications
- `training.{modelId}` - ML model training progress
- `system.health` - System health updates

## Authentication Flow
1. Public: Landing page
2. Guest: Login, Register, Password Reset
3. Authenticated: All other routes
4. Admin Only: Users, ML Models, System Health
5. Admin/Analyst: Network Logs, Alerts, Rules, Investigations, Live Monitoring
