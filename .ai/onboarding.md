# AI Agent Onboarding

> **Complete this checklist at the start of every new AI session or when switching AI tools.**  
> Do not begin implementation work until every item is checked.

---

## Step 1: Context Acquisition

Read each document and confirm understanding:

- [ ] `docs/PROJECT_CONTEXT.md` — I understand what this system does and who uses it
- [ ] `docs/ARCHITECTURE.md` — I understand the module structure and layer responsibilities
- [ ] `docs/BUSINESS_RULES.md` — I understand the domain rules and calculations
- [ ] `docs/DATABASE.md` — I understand the schema, relationships, and query standards
- [ ] `docs/DEVELOPMENT_RULES.md` — I understand the coding conventions and forbidden patterns
- [ ] `docs/SECURITY_STANDARDS.md` — I understand the security requirements
- [ ] `docs/API_STANDARDS.md` — I understand the API design conventions
- [ ] `docs/TESTING_STANDARDS.md` — I understand the test requirements
- [ ] `docs/UI_STANDARDS.md` — I understand the design standards
- [ ] `.ai/session_summary.md` — I understand the current development state

---

## Step 2: Project State Summary

Produce the following before any work begins:

**What is this project?**

> `[AI: Write 2–3 sentences describing the project based on PROJECT_CONTEXT.md]`

**What is the current state?**

> `[AI: Summarise what has been built and what is in progress based on session_summary.md]`

**What task has been requested this session?**

> `[AI: State the task clearly]`

**What existing code will be affected?**

> `[AI: List modules, files, or functions that will be touched]`

**Are there any risks or concerns with this task?**

> `[AI: Flag anything that could break existing functionality, security issues, or business rule conflicts]`

---

## Step 3: Pre-Work Validation

Confirm before writing any code:

- [ ] I have identified the correct module for this work
- [ ] I have checked for existing patterns I should follow
- [ ] I have checked that this task does not violate any business rule in `BUSINESS_RULES.md`
- [ ] I have checked that this task does not weaken any control in `SECURITY_STANDARDS.md`
- [ ] I know where the new tests should be written
- [ ] If this is a database change, I have a reversible migration plan

---

## Step 4: Implementation Mode

During implementation, follow these checkpoints:

### After each new class or function:
- [ ] Does it have a single responsibility?
- [ ] Is it testable in isolation?
- [ ] Does it follow the naming conventions in `DEVELOPMENT_RULES.md`?
- [ ] Is it placed in the correct module and layer?

### After each database migration:
- [ ] Does it have a working `down()` method?
- [ ] Does it follow the naming convention?
- [ ] Are indexes added for foreign keys and common query columns?

### After each API endpoint:
- [ ] Is it authenticated?
- [ ] Is it authorised?
- [ ] Is the response transformed through an API Resource?
- [ ] Is the input validated through a Form Request?
- [ ] Are the correct HTTP status codes returned?

### After each UI component:
- [ ] Does it follow the visual hierarchy standards in `UI_STANDARDS.md`?
- [ ] Is it responsive?
- [ ] Does it handle empty states, loading states, and error states?
- [ ] Does it meet basic accessibility requirements?

---

## Step 5: Session Closeout

Before ending every session:

- [ ] Update `.ai/session_summary.md` with completed work
- [ ] List all modified files
- [ ] Document any decisions made and why
- [ ] Note any remaining tasks
- [ ] Write the recommended next action clearly

---

## Compatibility Note

This framework is designed for use with any AI coding agent:

| Tool | How to Use |
|---|---|
| **Claude (claude.ai / API)** | Paste `system_prompt.md` as system prompt. Attach docs as files or context. |
| **ChatGPT** | Paste `system_prompt.md` as a system message or at the top of the conversation. |
| **Gemini** | Include `system_prompt.md` in the initial prompt. |
| **Cursor** | Add `.ai/` and `docs/` to the project context via `@` references. |
| **Continue (VS Code)** | Add files as context via `@file` commands. |
| **Aider** | Use `--read` flags: `aider --read .ai/system_prompt.md --read docs/*.md` |
| **Local Models (Ollama)** | Include system_prompt.md in the system role. |
