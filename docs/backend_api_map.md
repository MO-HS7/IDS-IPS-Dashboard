# Backend API Map

## Authentication Routes

| Route | Method | Controller@Action | Request Schema | Response Schema | Auth/Role | Queue/Jobs |
|-------|--------|------------------|---------------|-----------------|-----------|------------|
| /login | POST | Auth\AuthenticatedSessionController@store | `{email, password}` | `{user, token}` | Guest | None |
| /register | POST | Auth\RegisteredUserController@store | `{name, email, password, password_confirmation}` | `{user}` | Guest | SendEmailVerification |
| /logout | POST | Auth\AuthenticatedSessionController@destroy | None | None | Auth | None |
| /forgot-password | POST | Auth\PasswordResetLinkController@store | `{email}` | `{status}` | Guest | SendPasswordResetEmail |
| /reset-password | POST | Auth\NewPasswordController@store | `{token, email, password, password_confirmation}` | `{status}` | Guest | None |
| /email/verification-notification | POST | - | None | `{status}` | Auth | SendEmailVerification |

## Dashboard API

| Route | Method | Controller@Action | Request Schema | Response Schema | Auth/Role | Queue/Jobs |
|-------|--------|------------------|---------------|-----------------|-----------|------------|
| /dashboard | GET | DashboardController@index | None | Inertia View | Auth | None |
| /api/dashboard/stats | GET | DashboardController@stats | None | `{total_alerts, total_logs, active_models, critical_alerts}` | Auth | None |
| /api/dashboard/charts | GET | DashboardController@charts | None | `{alertsOverTime, severityDistribution, attackTypes}` | Auth | None |
| /api/dashboard/recent-alerts | GET | DashboardController@recentAlerts | `{limit?}` | `{data: Alert[]}` | Auth | None |

## Network Logs API

| Route | Method | Controller@Action | Request Schema | Response Schema | Auth/Role | Queue/Jobs |
|-------|--------|------------------|---------------|-----------------|-----------|------------|
| /network-logs | GET | NetworkLogController@index | `{page?, per_page?, search?, status?}` | `{data: NetworkLog[], links, meta}` | Admin/Analyst | None |
| /network-logs | POST | NetworkLogController@store | `{file: File, description?}` | `{networkLog}` | Admin/Analyst | ProcessPcapFile |
| /network-logs/{id} | GET | NetworkLogController@show | None | `{networkLog}` | Admin/Analyst | None |
| /network-logs/{id} | PUT | NetworkLogController@update | `{description?, status?}` | `{networkLog}` | Admin/Analyst | None |
| /network-logs/{id} | DELETE | NetworkLogController@destroy | None | `{message}` | Admin/Analyst | None |
| /api/pcap/preview | POST | NetworkLogController@preview | `{file: File}` | `{packets: [], summary}` | Admin/Analyst | None |
| /api/pcap/progress/{id} | GET | NetworkLogController@progress | None | `{status, progress, packets_processed}` | Admin/Analyst | None |
| /api/pcap/process-packet | POST | NetworkLogController@processPacket | `{packet_data}` | `{status}` | None (Webhook) | ProcessPacket |

## Alerts API

| Route | Method | Controller@Action | Request Schema | Response Schema | Auth/Role | Queue/Jobs |
|-------|--------|------------------|---------------|-----------------|-----------|------------|
| /alerts | GET | AlertController@index | `{page?, severity?, status?, date_from?, date_to?}` | `{data: Alert[], links, meta}` | Admin/Analyst | None |
| /alerts | POST | AlertController@store | `{attack_type, source_ip, destination_ip, severity, description}` | `{alert}` | Admin/Analyst | SendAlertNotification |
| /alerts/{id} | GET | AlertController@show | None | `{alert}` | Admin/Analyst | None |
| /alerts/{id} | PUT | AlertController@update | `{status?, notes?}` | `{alert}` | Admin/Analyst | None |
| /alerts/{id} | DELETE | AlertController@destroy | None | `{message}` | Admin/Analyst | None |

## ML Models API

| Route | Method | Controller@Action | Request Schema | Response Schema | Auth/Role | Queue/Jobs |
|-------|--------|------------------|---------------|-----------------|-----------|------------|
| /ml-models | GET | MLModelController@index | `{page?, status?}` | `{data: MLModel[], links, meta}` | Admin | None |
| /ml-models | POST | MLModelController@store | `{name, type, config}` | `{model}` | Admin | None |
| /ml-models/{id} | GET | MLModelController@show | None | `{model}` | Admin | None |
| /ml-models/{id} | PUT | MLModelController@update | `{name?, config?, status?}` | `{model}` | Admin | None |
| /ml-models/{id} | DELETE | MLModelController@destroy | None | `{message}` | Admin | None |
| /api/ml-models/{id}/start-training | POST | MLModelController@startTraining | `{dataset, parameters}` | `{sessionId, status}` | Admin | TrainMLModel |
| /api/ml-models/{id}/training-status/{sessionId} | GET | MLModelController@trainingStatus | None | `{status, progress, metrics}` | Admin | None |
| /api/ml-models/{id}/metrics | GET | MLModelController@metrics | None | `{accuracy, precision, recall, f1, confusion_matrix}` | Admin | None |
| /api/ml-models/{id}/activate | POST | MLModelController@activate | None | `{model}` | Admin | None |
| /api/ml-models/{id}/predictions | GET | MLModelController@predictions | `{limit?}` | `{predictions: []}` | Admin | None |

## Live Monitoring API

| Route | Method | Controller@Action | Request Schema | Response Schema | Auth/Role | Queue/Jobs |
|-------|--------|------------------|---------------|-----------------|-----------|------------|
| /live-monitoring | GET | LiveMonitoringController@index | None | Inertia View | Admin/Analyst | None |
| /api/live-monitoring/interfaces | GET | LiveMonitoringController@getInterfaces | None | `{interfaces: []}` | Admin/Analyst | None |
| /api/live-monitoring/start | POST | LiveMonitoringController@start | `{interface, filter?, duration?}` | `{sessionId, status}` | Admin/Analyst | StartPacketCapture |
| /api/live-monitoring/stop | POST | LiveMonitoringController@stop | `{sessionId}` | `{status}` | Admin/Analyst | None |
| /api/live-monitoring/poll | GET | LiveMonitoringController@pollPackets | `{sessionId, since?}` | `{packets: []}` | Admin/Analyst | None |
| /api/live-monitoring/packet | POST | LiveMonitoringController@receivePacket | `{packet_data}` | `{status}` | None (Webhook) | ProcessLivePacket |

## Rules API

| Route | Method | Controller@Action | Request Schema | Response Schema | Auth/Role | Queue/Jobs |
|-------|--------|------------------|---------------|-----------------|-----------|------------|
| /rules | GET | RuleController@index | `{page?, active?, type?}` | `{data: Rule[], links, meta}` | Admin/Analyst | None |
| /rules | POST | RuleController@store | `{name, rule_content, type, severity, enabled}` | `{rule}` | Admin/Analyst | None |
| /rules/{id} | GET | RuleController@show | None | `{rule}` | Admin/Analyst | None |
| /rules/{id} | PUT | RuleController@update | `{name?, rule_content?, severity?, enabled?}` | `{rule}` | Admin/Analyst | None |
| /rules/{id} | DELETE | RuleController@destroy | None | `{message}` | Admin/Analyst | None |
| /rules/{id}/toggle | POST | RuleController@toggle | None | `{rule}` | Admin/Analyst | None |
| /rules/import | POST | RuleController@import | `{file: File}` | `{imported_count}` | Admin/Analyst | ImportRules |
| /rules/export | GET | RuleController@export | None | File download | Admin/Analyst | None |

## Investigations API

| Route | Method | Controller@Action | Request Schema | Response Schema | Auth/Role | Queue/Jobs |
|-------|--------|------------------|---------------|-----------------|-----------|------------|
| /investigations | GET | InvestigationController@index | `{page?, status?}` | `{data: Investigation[], links, meta}` | Admin/Analyst | None |
| /investigations | POST | InvestigationController@store | `{title, description, priority}` | `{investigation}` | Admin/Analyst | None |
| /investigations/{id} | GET | InvestigationController@show | None | `{investigation}` | Admin/Analyst | None |
| /investigations/{id} | PUT | InvestigationController@update | `{title?, description?, status?, findings?}` | `{investigation}` | Admin/Analyst | None |
| /investigations/{id} | DELETE | InvestigationController@destroy | None | `{message}` | Admin/Analyst | None |
| /investigations/{id}/add-alert | POST | InvestigationController@addAlert | `{alert_id}` | `{investigation}` | Admin/Analyst | None |
| /investigations/{id}/alerts/{alertId} | DELETE | InvestigationController@removeAlert | None | `{investigation}` | Admin/Analyst | None |

## Analytics API

| Route | Method | Controller@Action | Request Schema | Response Schema | Auth/Role | Queue/Jobs |
|-------|--------|------------------|---------------|-----------------|-----------|------------|
| /analytics | GET | AnalyticsController@index | `{filter?, date_from?, date_to?}` | Inertia View | Auth | None |
| /analytics/export | GET | AnalyticsController@export | `{format?, period?}` | File download | Auth | GenerateAnalyticsReport |

## Users API

| Route | Method | Controller@Action | Request Schema | Response Schema | Auth/Role | Queue/Jobs |
|-------|--------|------------------|---------------|-----------------|-----------|------------|
| /users | GET | UserController@index | `{page?, search?, role?}` | `{data: User[], links, meta}` | Admin | None |
| /users | POST | UserController@store | `{name, email, password, role}` | `{user}` | Admin | SendWelcomeEmail |
| /users/{id} | GET | UserController@show | None | `{user}` | Admin | None |
| /users/{id} | PUT | UserController@update | `{name?, email?, role?, active?}` | `{user}` | Admin | None |
| /users/{id} | DELETE | UserController@destroy | None | `{message}` | Admin | None |

## System Health API

| Route | Method | Controller@Action | Request Schema | Response Schema | Auth/Role | Queue/Jobs |
|-------|--------|------------------|---------------|-----------------|-----------|------------|
| /system-health | GET | SystemHealthController@index | None | Inertia View | Admin | None |

## Queue Jobs

| Job Name | Purpose | Triggers Alert | Sends Notification | Auto-Response |
|----------|---------|---------------|-------------------|---------------|
| ProcessPcapFile | Extract features from PCAP, run ML inference | Yes | Yes (if severity > threshold) | Yes (if enabled) |
| ProcessPacket | Process single packet from live capture | Yes (if malicious) | Yes (if critical) | Yes (if enabled) |
| ProcessLivePacket | Process packet from live monitoring | Yes (if malicious) | Yes (if critical) | Yes (if enabled) |
| TrainMLModel | Train ML model with dataset | No | Yes (completion) | No |
| SendAlertNotification | Send email/webhook for alert | No | Yes | No |
| SendEmailVerification | Send verification email | No | Yes | No |
| SendPasswordResetEmail | Send password reset link | No | Yes | No |
| SendWelcomeEmail | Send welcome email to new user | No | Yes | No |
| ImportRules | Import Snort/Suricata rules | No | No | No |
| GenerateAnalyticsReport | Generate PDF/CSV analytics report | No | Yes (download link) | No |
| StartPacketCapture | Start tcpdump/tshark capture | No | No | No |

## WebSocket Events

| Event Name | Channel | Payload | Purpose |
|------------|---------|---------|---------|
| alert.created | alerts.{userId} | `{alert}` | New alert created |
| alert.updated | alerts.{userId} | `{alert}` | Alert status changed |
| packet.captured | network.live.{userId} | `{packet}` | Live packet captured |
| training.progress | training.{modelId} | `{progress, status}` | ML training update |
| pcap.processing | pcap.{logId} | `{progress, packets}` | PCAP processing status |
| notification.new | notifications.{userId} | `{notification}` | New notification |
| health.update | system.health | `{metrics}` | System health metrics |
| rule.updated | rules | `{rule}` | Rule enabled/disabled |
| investigation.updated | investigations.{id} | `{investigation}` | Investigation modified |
| model.status | ml.models | `{model, status}` | Model status change |
