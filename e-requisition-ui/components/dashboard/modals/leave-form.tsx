"use client";

import { useState } from "react";
import { cn } from "@/lib/utils";
import { X, CheckCircle2, AlertCircle, CalendarDays, Palmtree, Stethoscope, GraduationCap, Users, AlertTriangle, HelpCircle } from "lucide-react";

interface LeaveFormProps {
  open: boolean;
  onClose: () => void;
  onSubmit?: (data: { reason: string; start_date: string; end_date: string; notes: string }) => void;
}

const LEAVE_TYPES = [
  { value: "annual", label: "Annual Leave", description: "Planned vacation or personal time", icon: Palmtree, color: "text-chart-2" },
  { value: "sick", label: "Sick Leave", description: "Illness or medical appointment", icon: Stethoscope, color: "text-destructive" },
  { value: "family_responsibility", label: "Family Responsibility", description: "Family emergency or obligation", icon: Users, color: "text-chart-1" },
  { value: "study", label: "Study Leave", description: "Exams, courses or certification", icon: GraduationCap, color: "text-chart-5" },
  { value: "unpaid", label: "Unpaid Leave", description: "Extended leave without pay", icon: AlertTriangle, color: "text-warning" },
  { value: "other", label: "Other", description: "Any other reason", icon: HelpCircle, color: "text-muted-foreground" },
];

function countBusinessDays(start: string, end: string): number {
  if (!start || !end) return 0;
  let count = 0;
  const s = new Date(start);
  const e = new Date(end);
  const cur = new Date(s);
  while (cur <= e) {
    const day = cur.getDay();
    if (day !== 0 && day !== 6) count++;
    cur.setDate(cur.getDate() + 1);
  }
  return count;
}

const inputCls = "w-full h-10 px-3 rounded-lg bg-secondary border border-border text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent transition-all";

export function LeaveForm({ open, onClose, onSubmit }: LeaveFormProps) {
  const [reason, setReason] = useState("");
  const [startDate, setStartDate] = useState("");
  const [endDate, setEndDate] = useState("");
  const [notes, setNotes] = useState("");
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [submitting, setSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);

  const today = new Date().toISOString().split("T")[0];
  const businessDays = countBusinessDays(startDate, endDate);
  const isEndBeforeStart = startDate && endDate && endDate < startDate;

  function validate(): boolean {
    const e: Record<string, string> = {};
    if (!reason) e.reason = "Please select a leave type";
    if (!startDate) e.startDate = "Start date is required";
    if (!endDate) e.endDate = "End date is required";
    if (isEndBeforeStart) e.endDate = "End date must be after start date";
    if (reason === "sick" && !notes.trim()) e.notes = "Please provide details for sick leave";
    setErrors(e);
    return Object.keys(e).length === 0;
  }

  async function handleSubmit() {
    if (!validate()) return;
    setSubmitting(true);
    await new Promise(r => setTimeout(r, 1200));
    setSubmitting(false);
    setSubmitted(true);
    onSubmit?.({ reason, start_date: startDate, end_date: endDate, notes });
    setTimeout(() => {
      setSubmitted(false);
      handleClose();
    }, 2000);
  }

  function handleClose() {
    setReason("");
    setStartDate("");
    setEndDate("");
    setNotes("");
    setErrors({});
    setSubmitted(false);
    onClose();
  }

  if (!open) return null;

  const selectedType = LEAVE_TYPES.find(t => t.value === reason);

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={handleClose} />
      <div className="relative w-full max-w-lg bg-card border border-border rounded-2xl shadow-2xl flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-200">

        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-border shrink-0">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-lg bg-chart-1/10 flex items-center justify-center">
              <CalendarDays className="w-5 h-5 text-chart-1" />
            </div>
            <div>
              <h2 className="text-base font-semibold text-foreground">Request Leave</h2>
              <p className="text-xs text-muted-foreground">Submit a leave request for approval</p>
            </div>
          </div>
          <button onClick={handleClose} className="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-secondary text-muted-foreground hover:text-foreground transition-colors">
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Body */}
        <div className="flex-1 overflow-y-auto px-6 py-5 space-y-5">
          {submitted ? (
            <div className="flex flex-col items-center justify-center py-12 text-center">
              <div className="w-16 h-16 rounded-full bg-success/10 flex items-center justify-center mb-4">
                <CheckCircle2 className="w-8 h-8 text-success" />
              </div>
              <h3 className="text-lg font-semibold text-foreground">Leave Request Submitted!</h3>
              <p className="text-sm text-muted-foreground mt-1">Your request has been sent to your manager for approval.</p>
            </div>
          ) : (
            <>
              {/* Leave type */}
              <div className="space-y-2">
                <label className="text-sm font-medium text-foreground">Leave Type <span className="text-destructive">*</span></label>
                <div className="grid grid-cols-2 gap-2">
                  {LEAVE_TYPES.map(t => {
                    const Icon = t.icon;
                    return (
                      <button
                        key={t.value}
                        onClick={() => { setReason(t.value); setErrors(e => ({ ...e, reason: "" })); }}
                        className={cn(
                          "flex items-center gap-3 p-3 rounded-xl border-2 text-left transition-all",
                          reason === t.value
                            ? "border-accent bg-accent/5"
                            : "border-border hover:border-accent/40 hover:bg-secondary/50"
                        )}
                      >
                        <Icon className={cn("w-4 h-4 shrink-0", t.color)} />
                        <div className="min-w-0">
                          <p className="text-xs font-semibold text-foreground leading-tight">{t.label}</p>
                          <p className="text-[10px] text-muted-foreground leading-tight truncate">{t.description}</p>
                        </div>
                      </button>
                    );
                  })}
                </div>
                {errors.reason && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="w-3 h-3" />{errors.reason}</p>}
              </div>

              {/* Dates */}
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-foreground">Start Date <span className="text-destructive">*</span></label>
                  <input
                    type="date"
                    min={today}
                    value={startDate}
                    onChange={e => {
                      setStartDate(e.target.value);
                      setErrors(er => ({ ...er, startDate: "" }));
                      if (endDate && e.target.value > endDate) setEndDate("");
                    }}
                    className={cn(inputCls, errors.startDate && "border-destructive")}
                  />
                  {errors.startDate && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="w-3 h-3" />{errors.startDate}</p>}
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium text-foreground">End Date <span className="text-destructive">*</span></label>
                  <input
                    type="date"
                    min={startDate || today}
                    value={endDate}
                    onChange={e => { setEndDate(e.target.value); setErrors(er => ({ ...er, endDate: "" })); }}
                    className={cn(inputCls, errors.endDate && "border-destructive")}
                  />
                  {errors.endDate && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="w-3 h-3" />{errors.endDate}</p>}
                </div>
              </div>

              {/* Duration indicator */}
              {startDate && endDate && !isEndBeforeStart && (
                <div className="flex items-center gap-3 p-3 rounded-lg bg-secondary border border-border">
                  <CalendarDays className="w-4 h-4 text-accent shrink-0" />
                  <div>
                    <p className="text-sm font-semibold text-foreground">{businessDays} business day{businessDays !== 1 ? "s" : ""}</p>
                    <p className="text-xs text-muted-foreground">{startDate} → {endDate}</p>
                  </div>
                  {businessDays > 5 && (
                    <div className="ml-auto flex items-center gap-1 text-xs text-warning">
                      <AlertCircle className="w-3.5 h-3.5" />
                      Long leave
                    </div>
                  )}
                </div>
              )}

              {/* Notes */}
              <div className="space-y-1.5">
                <label className="text-sm font-medium text-foreground">
                  Notes {reason === "sick" && <span className="text-destructive">*</span>}
                  {reason !== "sick" && <span className="text-muted-foreground text-xs ml-1">(optional)</span>}
                </label>
                <textarea
                  rows={3}
                  placeholder={
                    reason === "sick" ? "Describe your illness or medical appointment..." :
                    reason === "study" ? "Exam name, institution, or course details..." :
                    "Any additional notes for your manager..."
                  }
                  value={notes}
                  onChange={e => { setNotes(e.target.value); setErrors(er => ({ ...er, notes: "" })); }}
                  className={cn(
                    "w-full px-3 py-2.5 rounded-lg bg-secondary border border-border text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent resize-none transition-all",
                    errors.notes && "border-destructive"
                  )}
                />
                {errors.notes && <p className="text-xs text-destructive flex items-center gap-1"><AlertCircle className="w-3 h-3" />{errors.notes}</p>}
              </div>

              {/* Leave balance info */}
              <div className="p-3 rounded-lg bg-secondary border border-border">
                <p className="text-xs font-semibold text-foreground mb-2">Your Leave Balance</p>
                <div className="grid grid-cols-3 gap-3">
                  {[
                    { label: "Annual", balance: 9 },
                    { label: "Sick", balance: 30 },
                    { label: "Study", balance: 5 },
                  ].map(b => (
                    <div key={b.label} className="text-center">
                      <p className="text-lg font-bold text-foreground">{b.balance}</p>
                      <p className="text-[10px] text-muted-foreground">{b.label}</p>
                    </div>
                  ))}
                </div>
              </div>
            </>
          )}
        </div>

        {/* Footer */}
        {!submitted && (
          <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-border shrink-0">
            <button onClick={handleClose} className="px-4 py-2 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-secondary transition-colors">
              Cancel
            </button>
            <button
              onClick={handleSubmit}
              disabled={submitting}
              className="flex items-center gap-2 px-5 py-2 rounded-lg bg-accent text-accent-foreground text-sm font-medium hover:bg-accent/90 transition-colors disabled:opacity-70"
            >
              {submitting ? (
                <><span className="w-4 h-4 border-2 border-accent-foreground/30 border-t-accent-foreground rounded-full animate-spin" />Submitting...</>
              ) : (
                <><CheckCircle2 className="w-4 h-4" />Submit Request</>
              )}
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
