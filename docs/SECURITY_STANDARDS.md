# Security Standards

> **AI AGENT INSTRUCTION:** Security is non-negotiable. Never disable, bypass, or weaken any security control listed in this file. If a task requires a security trade-off, raise it explicitly before implementing.

---

## Authentication

### Password Handling

```php
// Always use Laravel's built-in hashing — never md5, sha1, or plain text
$hashed = Hash::make($plainPassword);
Hash::check($plainPassword, $hashed);
```

- Minimum password length: **12 characters**
- Require at least one uppercase, one lowercase, one number, one symbol
- Enforce via `Password::min(12)->mixedCase()->numbers()->symbols()`
- Never log or transmit plain-text passwords
- Implement account lockout after **5 failed attempts** (use throttling middleware)

### Multi-Factor Authentication

- MFA must be supported for all administrator accounts
- Use TOTP (e.g. Google Authenticator compatible) or SMS OTP
- MFA bypass codes must be stored hashed

### Session Management

```php
// config/session.php production settings
'driver'          => 'redis',
'lifetime'        => 120,          // 2 hours idle timeout
'expire_on_close' => true,         // invalidate on browser close
'secure'          => true,         // HTTPS only
'http_only'       => true,         // no JS access
'same_site'       => 'lax',        // CSRF protection
'encrypt'         => true,         // encrypt session data
```

---

## Authorisation

### Role-Based Access Control

```php
// Policies for all model-level actions
class PensionApplicationPolicy
{
    public function view(User $user, PensionApplication $application): bool
    {
        return $user->hasRole('admin') || $user->id === $application->user_id;
    }

    public function approve(User $user, PensionApplication $application): bool
    {
        return $user->hasRole('approver');
    }
}
```

### Route and Controller Guards

```php
// Always apply middleware at route level
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('applications', PensionApplicationController::class);
});

// And check in controllers for model-level actions
$this->authorize('approve', $application);
```

---

## OWASP Top 10 Mitigations

| Threat | Mitigation |
|---|---|
| **Injection** | Eloquent ORM / parameterised queries only. Never raw concatenation. |
| **Broken Authentication** | Laravel Sanctum / Fortify. Session encryption. MFA. |
| **Sensitive Data Exposure** | Encrypted at rest. HTTPS only. No sensitive data in logs. |
| **XXE** | Disable external XML entities in `libxml` calls. |
| **Broken Access Control** | Policies on every model. Authorisation gates on routes. |
| **Security Misconfiguration** | `APP_DEBUG=false` in production. No verbose errors exposed. |
| **XSS** | Blade auto-escapes `{{ }}`. Never use `{!! !!}` on user input. |
| **Insecure Deserialisation** | Never `unserialize()` user input. Use JSON. |
| **Known Vulnerabilities** | `composer audit` in CI/CD pipeline. Regular dependency updates. |
| **Insufficient Logging** | Structured logging for all auth events and sensitive operations. |

---

## Input Validation

```php
// All input must pass through Form Request validation before use
// Never access $request->input() directly in business logic
// Never trust client-side validation alone

// Validate file uploads explicitly
'document' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
```

---

## CSRF Protection

- Laravel's CSRF middleware is always enabled on `web` routes
- API routes use token-based auth (Sanctum) — not session CSRF
- Never disable `VerifyCsrfToken` middleware

---

## Rate Limiting

```php
// routes/web.php — protect login and sensitive endpoints
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');   // 5 attempts per minute

// routes/api.php
Route::middleware('throttle:api')->group(function () {
    // API routes
});
```

---

## Data Encryption

### Encryption at Rest

```php
// Encrypt sensitive model attributes using Eloquent casts
protected $casts = [
    'national_id'       => 'encrypted',
    'bank_account'      => 'encrypted',
    'date_of_birth'     => 'encrypted:date',
];
```

### Encryption in Transit

- All traffic served over HTTPS (TLS 1.2 minimum, TLS 1.3 preferred)
- HTTP redirects to HTTPS at Nginx level
- HSTS header enabled

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

---

## HTTP Security Headers

```nginx
# nginx site config
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';" always;
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
```

---

## Infrastructure Security

### Firewall (UFW)

```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp    # SSH (restrict to known IPs if possible)
ufw allow 80/tcp    # HTTP (redirects to HTTPS)
ufw allow 443/tcp   # HTTPS
ufw enable
```

### SSH Hardening

```bash
# /etc/ssh/sshd_config
PasswordAuthentication no
PermitRootLogin no
AllowUsers [deploy_user]
MaxAuthTries 3
ClientAliveInterval 300
ClientAliveCountMax 2
```

### Docker Security

```yaml
# docker-compose.yml — restrict container privileges
services:
  app:
    read_only: false           # set true where possible
    security_opt:
      - no-new-privileges:true
    user: "1000:1000"          # run as non-root
```

- Never run containers as root in production
- Do not expose database or Redis ports to the host in production
- Keep Docker and base images updated

---

## Secrets Management Rules

- All secrets in `.env` file — never in code or Git
- `.env` is excluded from version control via `.gitignore`
- `.env.example` contains all keys with placeholder values — no real secrets
- Rotate secrets immediately if accidentally committed
- Use a secrets manager (Vault, AWS Secrets Manager) for production at scale

---

## Security Logging

> Every security event must be logged. AI agents must include logging for all of the following:

```php
// Events to always log
Log::info('User logged in',       ['user_id' => $user->id, 'ip' => request()->ip()]);
Log::warning('Login failed',      ['email' => $email,       'ip' => request()->ip()]);
Log::warning('Access denied',     ['user_id' => $user->id, 'route' => request()->path()]);
Log::info('Permission changed',   ['target_user' => $id,   'by' => auth()->id()]);
Log::info('Sensitive data viewed',['record' => $model->id, 'user' => auth()->id()]);
```

---

## Incident Response

| Severity | Response Time | Actions |
|---|---|---|
| **Critical** (data breach, system compromise) | Immediate | Isolate → notify owner → investigate → report |
| **High** (auth bypass, privilege escalation) | < 1 hour | Patch → test → deploy hotfix |
| **Medium** (rate limit bypass, minor data leak) | < 24 hours | Assess → plan fix → schedule |
| **Low** (misconfiguration, information disclosure) | < 1 week | Log → fix in next release |
