# Health Endpoint
Since version **1.4.0**, the framework ships a public health check:

```
GET /_zubzet/health
```

It performs an explicit database roundtrip (`SELECT 1`) and reports the result as JSON. The response never contains error details, so the endpoint is safe to expose publicly for uptime monitoring, downtime alerts, and readiness checks during application startup.

## Responses
| Condition | Status | Body |
| --------- | ------ | ---- |
| Application and database reachable | `200` | `{"status": "healthy"}` |
| Database unreachable | `503` | `{"status": "unhealthy"}` |

## Disabling
The endpoint is enabled by default. Turn it off in `z_settings.ini`:

```ini
health_endpoint_enabled = false
```

When disabled, the route is not registered and `/_zubzet/health` answers `404`.