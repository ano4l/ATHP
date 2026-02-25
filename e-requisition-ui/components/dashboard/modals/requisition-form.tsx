"use client";

import { useState, useRef } from "react";
import { cn } from "@/lib/utils";
import {
  X, ChevronRight, ChevronLeft, Upload, Trash2,
  FileText, DollarSign, Paperclip, CheckCircle2, AlertCircle
} from "lucide-react";

interface RequisitionFormProps {
  open: boolean;
  onClose: () => void;
  onSubmit?: (data: FormData) => void;
}

const STEPS = ["Type & Project", "Amount & Details", "Attachments", "Review"];

const CATEGORIES = [
  { value: "operations", label: "Operations" },
  { value: "project", label: "Project" },
  { value: "emergency", label: "Emergency" },
  { value: "client", label: "Client Related" },
  { value: "procurement", label: "Procurement" },
  { value: "travel", label: "Travel" },
  { value: "other", label: "Other" },
];

const BRANCHES = [
  { value: "south_africa", label: "South Africa", currency: "ZAR" },
  { value: "zambia", label: "Zambia", currency: "ZMW" },
  { value: "eswatini", label: "Eswatini", currency: "SZL" },
  { value: "zimbabwe", label: "Zimbabwe", currency: "USD" },
];

interface FormState {
  requisition_type: "cash" | "purchase";
  project_name: string;
  project_code: string;
  category: string;
  branch: string;
  currency: string;
  amount: string;
  purpose: string;
  cost_center: string;
  budget_code: string;
  needed_by: string;
  requisition_for: string;
  client_ref: string;
  order_ref: string;
}

const initial: FormState = {
  requisition_type: "cash",
  project_name: "",
  project_code: "",
  category: "",
  branch: "",
  currency: "",
  amount: "",
  purpose: "",
  cost_center: "",
  budget_code: "",
  needed_by: "",
  requisition_for: "internal",
  client_ref: "",
  order_ref: "",
};

function Field({ label, required, error, children }: { label: string; required?: boolean; error?: string; children: React.ReactNode }) {
  return (
    <div className="space-y-1.5">
      <label className="text-sm font-medium text-foreground">
        {label} {required && <span className="text-destructive">*</span>}
      </label>
      {children}
      {error && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="w-3 h-3" />{error}</p>}
    </div>
  );
}

const inputCls = "w-full h-10 px-3 rounded-lg bg-secondary border border-border text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent transition-all";
const selectCls = "w-full h-10 px-3 rounded-lg bg-secondary border border-border text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent transition-all";

export function RequisitionForm({ open, onClose, onSubmit }: RequisitionFormProps) {
  const [step, setStep] = useState(0);
  const [form, setForm] = useState<FormState>(initial);
  const [errors, setErrors] = useState<Partial<Record<keyof FormState, string>>>({});
  const [files, setFiles] = useState<File[]>([]);
  const [submitting, setSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const fileRef = useRef<HTMLInputElement>(null);

  const set = (k: keyof FormState, v: string) => {
    setForm(f => ({ ...f, [k]: v }));
    setErrors(e => ({ ...e, [k]: undefined }));
    if (k === "branch") {
      const b = BRANCHES.find(b => b.value === v);
      if (b) setForm(f => ({ ...f, branch: v, currency: b.currency }));
    }
  };

  function validateStep(): boolean {
    const e: Partial<Record<keyof FormState, string>> = {};
    if (step === 0) {
      if (!form.project_name.trim()) e.project_name = "Project name is required";
      if (!form.category) e.category = "Category is required";
      if (!form.branch) e.branch = "Branch is required";
    }
    if (step === 1) {
      if (!form.amount || parseFloat(form.amount) <= 0) e.amount = "Valid amount is required";
      if (!form.purpose.trim()) e.purpose = "Business justification is required";
      if (!form.needed_by) e.needed_by = "Required date is needed";
    }
    setErrors(e);
    return Object.keys(e).length === 0;
  }

  function next() { if (validateStep()) setStep(s => Math.min(s + 1, STEPS.length - 1)); }
  function back() { setStep(s => Math.max(s - 1, 0)); }

  function addFiles(newFiles: FileList | null) {
    if (!newFiles) return;
    const arr = Array.from(newFiles).filter(f => f.size <= 10 * 1024 * 1024);
    setFiles(prev => [...prev, ...arr]);
  }

  function removeFile(i: number) { setFiles(f => f.filter((_, idx) => idx !== i)); }

  async function handleSubmit() {
    setSubmitting(true);
    await new Promise(r => setTimeout(r, 1200));
    setSubmitting(false);
    setSubmitted(true);
    setTimeout(() => {
      setSubmitted(false);
      setStep(0);
      setForm(initial);
      setFiles([]);
      onClose();
    }, 2000);
  }

  function handleClose() {
    setStep(0);
    setForm(initial);
    setFiles([]);
    setErrors({});
    setSubmitted(false);
    onClose();
  }

  if (!open) return null;

  const branch = BRANCHES.find(b => b.value === form.branch);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={handleClose} />
      <div className="relative w-full max-w-2xl bg-card border border-border rounded-2xl shadow-2xl flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-200">

        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-border shrink-0">
          <div>
            <h2 className="text-lg font-semibold text-foreground">New Requisition</h2>
            <p className="text-xs text-muted-foreground mt-0.5">Step {step + 1} of {STEPS.length} — {STEPS[step]}</p>
          </div>
          <button onClick={handleClose} className="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-secondary text-muted-foreground hover:text-foreground transition-colors">
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Step indicator */}
        <div className="px-6 pt-4 shrink-0">
          <div className="flex items-center gap-2">
            {STEPS.map((s, i) => (
              <div key={s} className="flex items-center gap-2 flex-1">
                <div className={cn(
                  "w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 transition-all",
                  i < step ? "bg-accent text-accent-foreground" :
                  i === step ? "bg-accent text-accent-foreground ring-4 ring-accent/20" :
                  "bg-secondary text-muted-foreground"
                )}>
                  {i < step ? <CheckCircle2 className="w-4 h-4" /> : i + 1}
                </div>
                {i < STEPS.length - 1 && (
                  <div className={cn("h-0.5 flex-1 rounded-full transition-all", i < step ? "bg-accent" : "bg-border")} />
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Body */}
        <div className="flex-1 overflow-y-auto px-6 py-5">
          {submitted ? (
            <div className="flex flex-col items-center justify-center py-12 text-center">
              <div className="w-16 h-16 rounded-full bg-success/10 flex items-center justify-center mb-4">
                <CheckCircle2 className="w-8 h-8 text-success" />
              </div>
              <h3 className="text-lg font-semibold text-foreground">Requisition Submitted!</h3>
              <p className="text-sm text-muted-foreground mt-1">Your requisition has been submitted for approval.</p>
            </div>
          ) : (
            <>
              {/* Step 0: Type & Project */}
              {step === 0 && (
                <div className="space-y-4">
                  <Field label="Requisition Type" required>
                    <div className="grid grid-cols-2 gap-3">
                      {(["cash", "purchase"] as const).map(t => (
                        <button
                          key={t}
                          onClick={() => set("requisition_type", t)}
                          className={cn(
                            "flex items-center gap-3 p-4 rounded-xl border-2 text-left transition-all",
                            form.requisition_type === t
                              ? "border-accent bg-accent/5"
                              : "border-border hover:border-accent/50"
                          )}
                        >
                          {t === "cash" ? <DollarSign className="w-5 h-5 text-accent shrink-0" /> : <FileText className="w-5 h-5 text-accent shrink-0" />}
                          <div>
                            <p className="text-sm font-semibold text-foreground">{t === "cash" ? "Cash Requisition" : "Purchase Requisition"}</p>
                            <p className="text-xs text-muted-foreground">{t === "cash" ? "Direct cash disbursement" : "Goods or services"}</p>
                          </div>
                        </button>
                      ))}
                    </div>
                  </Field>

                  <div className="grid grid-cols-2 gap-4">
                    <Field label="Project Name" required error={errors.project_name}>
                      <input className={cn(inputCls, errors.project_name && "border-destructive")} placeholder="e.g. Copperline Rollout" value={form.project_name} onChange={e => set("project_name", e.target.value)} />
                    </Field>
                    <Field label="Project Code / ID">
                      <input className={inputCls} placeholder="e.g. PRJ-2026-001" value={form.project_code} onChange={e => set("project_code", e.target.value)} />
                    </Field>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <Field label="Category" required error={errors.category}>
                      <select className={cn(selectCls, errors.category && "border-destructive")} value={form.category} onChange={e => set("category", e.target.value)}>
                        <option value="">Select category...</option>
                        {CATEGORIES.map(c => <option key={c.value} value={c.value}>{c.label}</option>)}
                      </select>
                    </Field>
                    <Field label="Branch / Region" required error={errors.branch}>
                      <select className={cn(selectCls, errors.branch && "border-destructive")} value={form.branch} onChange={e => set("branch", e.target.value)}>
                        <option value="">Select branch...</option>
                        {BRANCHES.map(b => <option key={b.value} value={b.value}>{b.label} ({b.currency})</option>)}
                      </select>
                    </Field>
                  </div>

                  <Field label="Requisition For">
                    <div className="flex gap-3">
                      {["internal", "client", "project"].map(v => (
                        <label key={v} className="flex items-center gap-2 cursor-pointer">
                          <input type="radio" name="req_for" value={v} checked={form.requisition_for === v} onChange={() => set("requisition_for", v)} className="accent-accent" />
                          <span className="text-sm text-foreground capitalize">{v}</span>
                        </label>
                      ))}
                    </div>
                  </Field>

                  {form.requisition_for === "client" && (
                    <div className="grid grid-cols-2 gap-4">
                      <Field label="Client Reference">
                        <input className={inputCls} placeholder="Client ref..." value={form.client_ref} onChange={e => set("client_ref", e.target.value)} />
                      </Field>
                      <Field label="Order Reference">
                        <input className={inputCls} placeholder="Order ref..." value={form.order_ref} onChange={e => set("order_ref", e.target.value)} />
                      </Field>
                    </div>
                  )}
                </div>
              )}

              {/* Step 1: Amount & Details */}
              {step === 1 && (
                <div className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <Field label="Amount" required error={errors.amount}>
                      <div className="relative">
                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground">{form.currency || "—"}</span>
                        <input
                          type="number"
                          min="0"
                          step="0.01"
                          className={cn(inputCls, "pl-14", errors.amount && "border-destructive")}
                          placeholder="0.00"
                          value={form.amount}
                          onChange={e => set("amount", e.target.value)}
                        />
                      </div>
                    </Field>
                    <Field label="Required By" required error={errors.needed_by}>
                      <input
                        type="date"
                        className={cn(inputCls, errors.needed_by && "border-destructive")}
                        min={new Date().toISOString().split("T")[0]}
                        value={form.needed_by}
                        onChange={e => set("needed_by", e.target.value)}
                      />
                    </Field>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <Field label="Cost Centre">
                      <input className={inputCls} placeholder="e.g. CC-OPS-001" value={form.cost_center} onChange={e => set("cost_center", e.target.value)} />
                    </Field>
                    <Field label="Budget Code">
                      <input className={inputCls} placeholder="e.g. BUD-2026-Q1" value={form.budget_code} onChange={e => set("budget_code", e.target.value)} />
                    </Field>
                  </div>

                  <Field label="Business Justification" required error={errors.purpose}>
                    <textarea
                      rows={4}
                      className={cn("w-full px-3 py-2.5 rounded-lg bg-secondary border border-border text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent resize-none transition-all", errors.purpose && "border-destructive")}
                      placeholder="Describe the business purpose and justification for this requisition..."
                      value={form.purpose}
                      onChange={e => set("purpose", e.target.value)}
                    />
                    <p className="text-xs text-muted-foreground text-right">{form.purpose.length} chars</p>
                  </Field>

                  {form.amount && parseFloat(form.amount) >= 10000 && (
                    <div className="flex items-start gap-3 p-3 rounded-lg bg-warning/10 border border-warning/30">
                      <AlertCircle className="w-4 h-4 text-warning shrink-0 mt-0.5" />
                      <p className="text-xs text-warning">This amount exceeds the threshold and will require <strong>Stage 2 approval</strong>.</p>
                    </div>
                  )}
                </div>
              )}

              {/* Step 2: Attachments */}
              {step === 2 && (
                <div className="space-y-4">
                  <div
                    className="border-2 border-dashed border-border rounded-xl p-8 text-center hover:border-accent/50 transition-colors cursor-pointer"
                    onClick={() => fileRef.current?.click()}
                    onDragOver={e => e.preventDefault()}
                    onDrop={e => { e.preventDefault(); addFiles(e.dataTransfer.files); }}
                  >
                    <Upload className="w-8 h-8 text-muted-foreground mx-auto mb-3" />
                    <p className="text-sm font-medium text-foreground">Drop files here or click to upload</p>
                    <p className="text-xs text-muted-foreground mt-1">Quotations, invoices, supporting docs — max 10MB each</p>
                    <input ref={fileRef} type="file" multiple className="hidden" onChange={e => addFiles(e.target.files)} accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" />
                  </div>

                  {files.length > 0 && (
                    <div className="space-y-2">
                      {files.map((f, i) => (
                        <div key={i} className="flex items-center gap-3 p-3 rounded-lg bg-secondary border border-border">
                          <Paperclip className="w-4 h-4 text-muted-foreground shrink-0" />
                          <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-foreground truncate">{f.name}</p>
                            <p className="text-xs text-muted-foreground">{(f.size / 1024).toFixed(1)} KB</p>
                          </div>
                          <button onClick={() => removeFile(i)} className="text-muted-foreground hover:text-destructive transition-colors">
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      ))}
                    </div>
                  )}

                  {files.length === 0 && (
                    <p className="text-xs text-center text-muted-foreground">No attachments added. Some categories may require supporting documents.</p>
                  )}
                </div>
              )}

              {/* Step 3: Review */}
              {step === 3 && (
                <div className="space-y-4">
                  <div className="bg-secondary rounded-xl p-4 space-y-3">
                    <h3 className="text-sm font-semibold text-foreground">Requisition Summary</h3>
                    {[
                      ["Type", form.requisition_type === "cash" ? "Cash Requisition" : "Purchase Requisition"],
                      ["Project", form.project_name],
                      ["Category", CATEGORIES.find(c => c.value === form.category)?.label || "—"],
                      ["Branch", BRANCHES.find(b => b.value === form.branch)?.label || "—"],
                      ["Amount", form.amount ? `${form.currency} ${parseFloat(form.amount).toLocaleString("en", { minimumFractionDigits: 2 })}` : "—"],
                      ["Required By", form.needed_by || "—"],
                      ["Cost Centre", form.cost_center || "—"],
                      ["Budget Code", form.budget_code || "—"],
                      ["Attachments", files.length > 0 ? `${files.length} file(s)` : "None"],
                    ].map(([k, v]) => (
                      <div key={k} className="flex items-start justify-between gap-4">
                        <span className="text-xs text-muted-foreground shrink-0">{k}</span>
                        <span className="text-xs font-medium text-foreground text-right">{v}</span>
                      </div>
                    ))}
                  </div>
                  <div className="bg-secondary rounded-xl p-4">
                    <h3 className="text-xs font-semibold text-muted-foreground mb-1">Business Justification</h3>
                    <p className="text-sm text-foreground">{form.purpose || "—"}</p>
                  </div>
                  <div className="flex items-start gap-3 p-3 rounded-lg bg-chart-1/10 border border-chart-1/20">
                    <AlertCircle className="w-4 h-4 text-chart-1 shrink-0 mt-0.5" />
                    <p className="text-xs text-foreground">By submitting, this requisition will be sent to the approver and a reference number will be generated.</p>
                  </div>
                </div>
              )}
            </>
          )}
        </div>

        {/* Footer */}
        {!submitted && (
          <div className="flex items-center justify-between px-6 py-4 border-t border-border shrink-0">
            <button
              onClick={back}
              disabled={step === 0}
              className="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-secondary transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <ChevronLeft className="w-4 h-4" /> Back
            </button>
            {step < STEPS.length - 1 ? (
              <button onClick={next} className="flex items-center gap-2 px-5 py-2 rounded-lg bg-accent text-accent-foreground text-sm font-medium hover:bg-accent/90 transition-colors">
                Next <ChevronRight className="w-4 h-4" />
              </button>
            ) : (
              <button
                onClick={handleSubmit}
                disabled={submitting}
                className="flex items-center gap-2 px-5 py-2 rounded-lg bg-accent text-accent-foreground text-sm font-medium hover:bg-accent/90 transition-colors disabled:opacity-70"
              >
                {submitting ? (
                  <><span className="w-4 h-4 border-2 border-accent-foreground/30 border-t-accent-foreground rounded-full animate-spin" />Submitting...</>
                ) : (
                  <><CheckCircle2 className="w-4 h-4" />Submit Requisition</>
                )}
              </button>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
