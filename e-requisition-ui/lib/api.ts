const API_BASE = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000/api";

let authToken: string | null = null;

if (typeof window !== "undefined") {
  authToken = localStorage.getItem("auth_token");
}

function headers(isFormData = false): HeadersInit {
  const h: HeadersInit = {
    Accept: "application/json",
  };
  if (!isFormData) {
    h["Content-Type"] = "application/json";
  }
  if (authToken) {
    h["Authorization"] = `Bearer ${authToken}`;
  }
  return h;
}

async function handleResponse(res: Response) {
  if (res.status === 401) {
    authToken = null;
    if (typeof window !== "undefined") {
      localStorage.removeItem("auth_token");
      localStorage.removeItem("user");
      window.dispatchEvent(new Event("auth:logout"));
    }
    throw new Error("Unauthorized");
  }
  if (!res.ok) {
    const body = await res.json().catch(() => ({}));
    throw new Error(body.message || `Request failed (${res.status})`);
  }
  return res.json();
}

// ── Auth ──────────────────────────────────────────────────────────────

export async function login(email: string, password: string) {
  const res = await fetch(`${API_BASE}/login`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify({ email, password }),
  });
  const data = await handleResponse(res);
  authToken = data.token;
  if (typeof window !== "undefined") {
    localStorage.setItem("auth_token", data.token);
    localStorage.setItem("user", JSON.stringify(data.user));
  }
  return data;
}

export async function getMe() {
  const res = await fetch(`${API_BASE}/me`, { headers: headers() });
  return handleResponse(res);
}

export async function logout() {
  try {
    await fetch(`${API_BASE}/logout`, { method: "POST", headers: headers() });
  } catch (e) {
    // Even if API call fails, clear local tokens
    console.error("Logout API call failed:", e);
  } finally {
    authToken = null;
    if (typeof window !== "undefined") {
      localStorage.removeItem("auth_token");
      localStorage.removeItem("user");
    }
  }
}

export function getStoredUser() {
  if (typeof window === "undefined") return null;
  const raw = localStorage.getItem("user");
  return raw ? JSON.parse(raw) : null;
}

export function isLoggedIn(): boolean {
  if (typeof window === "undefined") return false;
  return !!localStorage.getItem("auth_token");
}

// ── Dashboard ─────────────────────────────────────────────────────────

export async function getDashboard() {
  const res = await fetch(`${API_BASE}/dashboard`, { headers: headers() });
  return handleResponse(res);
}

// ── Requisitions ──────────────────────────────────────────────────────

export async function getRequisitions(status?: string, page = 1) {
  const params = new URLSearchParams({ page: String(page) });
  if (status) params.set("status", status);
  const res = await fetch(`${API_BASE}/requisitions?${params}`, { headers: headers() });
  return handleResponse(res);
}

export async function getRequisition(id: number) {
  const res = await fetch(`${API_BASE}/requisitions/${id}`, { headers: headers() });
  return handleResponse(res);
}

export async function createRequisition(data: FormData) {
  const res = await fetch(`${API_BASE}/requisitions`, {
    method: "POST",
    headers: headers(true),
    body: data,
  });
  return handleResponse(res);
}

export async function approveRequisition(id: number, comment?: string) {
  const res = await fetch(`${API_BASE}/requisitions/${id}/approve`, {
    method: "POST",
    headers: headers(),
    body: JSON.stringify({ comment }),
  });
  return handleResponse(res);
}

export async function denyRequisition(id: number, comment: string) {
  const res = await fetch(`${API_BASE}/requisitions/${id}/deny`, {
    method: "POST",
    headers: headers(),
    body: JSON.stringify({ comment }),
  });
  return handleResponse(res);
}

export async function requestModification(id: number, comment: string) {
  const res = await fetch(`${API_BASE}/requisitions/${id}/modify`, {
    method: "POST",
    headers: headers(),
    body: JSON.stringify({ comment }),
  });
  return handleResponse(res);
}

export async function processRequisition(id: number, data: { payment_method: string; payment_reference: string; payment_date: string; finance_comment?: string }) {
  const res = await fetch(`${API_BASE}/requisitions/${id}/process`, {
    method: "POST",
    headers: headers(),
    body: JSON.stringify(data),
  });
  return handleResponse(res);
}

export async function fulfilRequisition(id: number, data: { actual_amount?: number; variance_reason?: string; fulfilment_notes?: string }) {
  const res = await fetch(`${API_BASE}/requisitions/${id}/fulfil`, {
    method: "POST",
    headers: headers(),
    body: JSON.stringify(data),
  });
  return handleResponse(res);
}

export async function closeRequisition(id: number, closure_comment?: string) {
  const res = await fetch(`${API_BASE}/requisitions/${id}/close`, {
    method: "POST",
    headers: headers(),
    body: JSON.stringify({ closure_comment }),
  });
  return handleResponse(res);
}

export async function uploadAttachment(requisitionId: number, file: File) {
  const form = new FormData();
  form.append("file", file);
  const res = await fetch(`${API_BASE}/requisitions/${requisitionId}/attachments`, {
    method: "POST",
    headers: headers(true),
    body: form,
  });
  return handleResponse(res);
}

// ── Leaves ────────────────────────────────────────────────────────────

export async function getLeaves(status?: string, page = 1) {
  const params = new URLSearchParams({ page: String(page) });
  if (status) params.set("status", status);
  const res = await fetch(`${API_BASE}/leaves?${params}`, { headers: headers() });
  return handleResponse(res);
}

export async function createLeave(data: { reason: string; start_date: string; end_date: string; notes?: string }) {
  const res = await fetch(`${API_BASE}/leaves`, {
    method: "POST",
    headers: headers(),
    body: JSON.stringify(data),
  });
  return handleResponse(res);
}

export async function approveLeave(id: number, comment?: string) {
  const res = await fetch(`${API_BASE}/leaves/${id}/approve`, {
    method: "POST",
    headers: headers(),
    body: JSON.stringify({ comment }),
  });
  return handleResponse(res);
}

export async function denyLeave(id: number, comment: string) {
  const res = await fetch(`${API_BASE}/leaves/${id}/deny`, {
    method: "POST",
    headers: headers(),
    body: JSON.stringify({ comment }),
  });
  return handleResponse(res);
}

// ── Notifications ─────────────────────────────────────────────────────

export async function getNotifications(page = 1) {
  const res = await fetch(`${API_BASE}/notifications?page=${page}`, { headers: headers() });
  return handleResponse(res);
}

export async function markNotificationRead(id: number) {
  const res = await fetch(`${API_BASE}/notifications/${id}/read`, {
    method: "POST",
    headers: headers(),
  });
  return handleResponse(res);
}

// ── Audit ─────────────────────────────────────────────────────────────

export async function getAuditLog(page = 1) {
  const res = await fetch(`${API_BASE}/audit?page=${page}`, { headers: headers() });
  return handleResponse(res);
}

// ── Reports ───────────────────────────────────────────────────────────

export async function getReports() {
  const res = await fetch(`${API_BASE}/reports`, { headers: headers() });
  return handleResponse(res);
}
