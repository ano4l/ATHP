"use client";

import { useState } from "react";
import { cn } from "@/lib/utils";
import { Plus, Search, Filter, CalendarDays, CheckCircle2, XCircle, Clock, Palmtree, Stethoscope, GraduationCap, Users, AlertTriangle, MessageSquare, Send, ChevronDown, ChevronUp } from "lucide-react";
import { LeaveForm } from "@/components/dashboard/modals/leave-form";

interface Comment {
  id: number;
  author: string;
  text: string;
  time: string;
}

interface LeaveRequest {
  id: number;
  employee: string;
  type: string;
  reason: string;
  startDate: string;
  endDate: string;
  days: number;
  status: "submitted" | "pending" | "approved" | "denied";
  branch: string;
  comments: Comment[];
}

const initialLeaves: LeaveRequest[] = [
  { id: 1, employee: "Jane Employee", type: "Annual", reason: "Family vacation", startDate: "24 Feb 2026", endDate: "28 Feb 2026", days: 5, status: "approved", branch: "Zambia", comments: [{ id: 1, author: "System", text: "Approved by Admin User", time: "22 Feb" }] },
  { id: 2, employee: "Admin User", type: "Sick", reason: "Medical appointment", startDate: "25 Feb 2026", endDate: "25 Feb 2026", days: 1, status: "pending", branch: "South Africa", comments: [{ id: 2, author: "Admin User", text: "Doctor's note will be provided on return.", time: "Today" }] },
  { id: 3, employee: "Jane Employee", type: "Study", reason: "Professional certification exam", startDate: "3 Mar 2026", endDate: "4 Mar 2026", days: 2, status: "pending", branch: "Zambia", comments: [] },
  { id: 4, employee: "Admin User", type: "Annual", reason: "Personal time off", startDate: "10 Mar 2026", endDate: "14 Mar 2026", days: 5, status: "submitted", branch: "South Africa", comments: [] },
  { id: 5, employee: "Jane Employee", type: "Family Responsibility", reason: "Child school event", startDate: "7 Mar 2026", endDate: "7 Mar 2026", days: 1, status: "approved", branch: "Zambia", comments: [{ id: 3, author: "System", text: "Approved by Admin User", time: "20 Feb" }] },
  { id: 6, employee: "Admin User", type: "Annual", reason: "Holiday travel", startDate: "1 Feb 2026", endDate: "5 Feb 2026", days: 5, status: "denied", branch: "South Africa", comments: [{ id: 4, author: "Admin User (Approver)", text: "Insufficient leave balance for this period.", time: "28 Jan" }, { id: 5, author: "System", text: "Denied by Admin User", time: "28 Jan" }] },
  { id: 7, employee: "Jane Employee", type: "Unpaid", reason: "Extended personal leave", startDate: "20 Jan 2026", endDate: "24 Jan 2026", days: 5, status: "approved", branch: "Zambia", comments: [{ id: 6, author: "System", text: "Approved by Admin User", time: "18 Jan" }] },
];

const statusConfig: Record<string, { color: string; bg: string; label: string }> = {
  submitted: { color: "text-chart-1", bg: "bg-chart-1/10", label: "Submitted" },
  pending: { color: "text-warning", bg: "bg-warning/10", label: "Pending" },
  approved: { color: "text-success", bg: "bg-success/10", label: "Approved" },
  denied: { color: "text-destructive", bg: "bg-destructive/10", label: "Denied" },
};

const typeIcons: Record<string, React.ElementType> = {
  Annual: Palmtree,
  Sick: Stethoscope,
  Study: GraduationCap,
  "Family Responsibility": Users,
  Unpaid: AlertTriangle,
};

export function LeavesSection() {
  const [leaves, setLeaves] = useState<LeaveRequest[]>(initialLeaves);
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const [commentInputs, setCommentInputs] = useState<Record<number, string>>({});
  const [confirmAction, setConfirmAction] = useState<{ id: number; action: "approve" | "deny" } | null>(null);
  const [formOpen, setFormOpen] = useState(false);

  const pendingCount = leaves.filter((l) => l.status === "pending" || l.status === "submitted").length;
  const approvedCount = leaves.filter((l) => l.status === "approved").length;
  const totalDaysUsed = leaves.filter((l) => l.status === "approved").reduce((sum, l) => sum + l.days, 0);

  function handleAction(id: number, action: "approve" | "deny") {
    const comment = commentInputs[id]?.trim();
    setLeaves((prev) =>
      prev.map((l) => {
        if (l.id !== id) return l;
        const newStatus = action === "approve" ? "approved" : "denied";
        const actionLabel = action === "approve" ? "Approved" : "Denied";
        const newComments = [...l.comments];
        if (comment) {
          newComments.push({ id: Date.now(), author: "You (Admin)", text: comment, time: "Just now" });
        }
        newComments.push({ id: Date.now() + 1, author: "System", text: `${actionLabel} by Admin User`, time: "Just now" });
        return { ...l, status: newStatus as LeaveRequest["status"], comments: newComments };
      })
    );
    setCommentInputs((prev) => ({ ...prev, [id]: "" }));
    setConfirmAction(null);
  }

  function addComment(id: number) {
    const text = commentInputs[id]?.trim();
    if (!text) return;
    setLeaves((prev) =>
      prev.map((l) => {
        if (l.id !== id) return l;
        return { ...l, comments: [...l.comments, { id: Date.now(), author: "You (Admin)", text, time: "Just now" }] };
      })
    );
    setCommentInputs((prev) => ({ ...prev, [id]: "" }));
  }

  return (
    <div className="space-y-6">
      <LeaveForm open={formOpen} onClose={() => setFormOpen(false)} />

      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-semibold text-foreground">Leave Management</h2>
          <p className="text-sm text-muted-foreground mt-0.5">Manage employee leave requests</p>
        </div>
        <button
          onClick={() => setFormOpen(true)}
          className="flex items-center gap-2 px-4 py-2.5 bg-accent text-accent-foreground rounded-lg text-sm font-medium hover:bg-accent/90 transition-colors"
        >
          <Plus className="w-4 h-4" />
          Request Leave
        </button>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div className="bg-card border border-border rounded-xl p-5">
          <div className="flex items-center gap-3 mb-2">
            <div className="w-9 h-9 rounded-lg bg-warning/10 flex items-center justify-center">
              <Clock className="w-4 h-4 text-warning" />
            </div>
            <span className="text-sm text-muted-foreground">Pending</span>
          </div>
          <span className="text-2xl font-bold text-foreground">{pendingCount}</span>
        </div>
        <div className="bg-card border border-border rounded-xl p-5">
          <div className="flex items-center gap-3 mb-2">
            <div className="w-9 h-9 rounded-lg bg-success/10 flex items-center justify-center">
              <CheckCircle2 className="w-4 h-4 text-success" />
            </div>
            <span className="text-sm text-muted-foreground">Approved</span>
          </div>
          <span className="text-2xl font-bold text-foreground">{approvedCount}</span>
        </div>
        <div className="bg-card border border-border rounded-xl p-5">
          <div className="flex items-center gap-3 mb-2">
            <div className="w-9 h-9 rounded-lg bg-chart-1/10 flex items-center justify-center">
              <CalendarDays className="w-4 h-4 text-chart-1" />
            </div>
            <span className="text-sm text-muted-foreground">Days Used</span>
          </div>
          <span className="text-2xl font-bold text-foreground">{totalDaysUsed}</span>
        </div>
        <div className="bg-card border border-border rounded-xl p-5">
          <div className="flex items-center gap-3 mb-2">
            <div className="w-9 h-9 rounded-lg bg-secondary flex items-center justify-center">
              <Palmtree className="w-4 h-4 text-muted-foreground" />
            </div>
            <span className="text-sm text-muted-foreground">Balance</span>
          </div>
          <span className="text-2xl font-bold text-foreground">9 days</span>
        </div>
      </div>

      <div className="flex items-center gap-3">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <input
            type="text"
            placeholder="Search leave requests..."
            className="w-full h-10 pl-9 pr-4 rounded-lg bg-card border border-border text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent transition-all"
          />
        </div>
        <button className="flex items-center gap-2 px-3 py-2.5 rounded-lg bg-card border border-border text-sm text-muted-foreground hover:text-foreground transition-colors">
          <Filter className="w-4 h-4" />
          Filters
        </button>
      </div>

      <div className="space-y-3">
        {leaves.map((leave, index) => {
          const status = statusConfig[leave.status];
          const TypeIcon = typeIcons[leave.type] || CalendarDays;
          const isPending = leave.status === "pending" || leave.status === "submitted";
          const isExpanded = expandedId === leave.id;
          const isConfirming = confirmAction?.id === leave.id;

          return (
            <div
              key={leave.id}
              className={cn(
                "bg-card border rounded-xl p-5 transition-all duration-300 animate-in fade-in slide-in-from-bottom-2",
                isPending ? "border-warning/30" : "border-border",
                !isPending && !isExpanded && "opacity-80"
              )}
              style={{ animationDelay: `${index * 60}ms`, animationFillMode: "both" }}
            >
              <div className="flex items-start justify-between">
                <div className="flex items-start gap-4">
                  <div className="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center shrink-0">
                    <TypeIcon className="w-5 h-5 text-muted-foreground" />
                  </div>
                  <div>
                    <div className="flex items-center gap-2 mb-1">
                      <span className="text-sm font-semibold text-foreground">{leave.employee}</span>
                      <span className={cn("inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium", status.bg, status.color)}>
                        {status.label}
                      </span>
                    </div>
                    <p className="text-sm text-foreground">{leave.type} — {leave.reason}</p>
                    <p className="text-xs text-muted-foreground mt-1">
                      {leave.startDate}{leave.startDate !== leave.endDate ? ` → ${leave.endDate}` : ""} • {leave.days} day{leave.days !== 1 ? "s" : ""} • {leave.branch}
                    </p>
                  </div>
                </div>
              </div>

              {/* Action buttons */}
              {isPending && (
                <div className="mt-4 pt-3 border-t border-border">
                  {isConfirming ? (
                    <div className="space-y-3">
                      <p className="text-sm text-foreground font-medium">
                        {confirmAction.action === "approve" ? "Confirm approval of this leave request?" : "Confirm denial of this leave request?"}
                      </p>
                      <textarea
                        value={commentInputs[leave.id] || ""}
                        onChange={(e) => setCommentInputs((prev) => ({ ...prev, [leave.id]: e.target.value }))}
                        placeholder={confirmAction.action === "deny" ? "Reason for denial (required)..." : "Add a comment (optional)..."}
                        rows={2}
                        className="w-full px-3 py-2 rounded-lg bg-secondary border border-border text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent resize-none"
                      />
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => {
                            if (confirmAction.action === "deny" && !commentInputs[leave.id]?.trim()) return;
                            handleAction(leave.id, confirmAction.action);
                          }}
                          disabled={confirmAction.action === "deny" && !commentInputs[leave.id]?.trim()}
                          className={cn(
                            "flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-colors",
                            confirmAction.action === "approve" && "bg-success text-white hover:bg-success/90",
                            confirmAction.action === "deny" && "bg-destructive text-white hover:bg-destructive/90 disabled:opacity-50 disabled:cursor-not-allowed"
                          )}
                        >
                          <CheckCircle2 className="w-4 h-4" />
                          Confirm
                        </button>
                        <button
                          onClick={() => setConfirmAction(null)}
                          className="px-4 py-2 rounded-lg bg-secondary text-muted-foreground text-sm font-medium hover:text-foreground transition-colors"
                        >
                          Cancel
                        </button>
                      </div>
                    </div>
                  ) : (
                    <div className="flex items-center gap-2">
                      <button
                        onClick={() => setConfirmAction({ id: leave.id, action: "approve" })}
                        className="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-success/10 text-success text-sm font-medium hover:bg-success/20 transition-colors"
                      >
                        <CheckCircle2 className="w-4 h-4" />
                        Approve
                      </button>
                      <button
                        onClick={() => setConfirmAction({ id: leave.id, action: "deny" })}
                        className="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-destructive/10 text-destructive text-sm font-medium hover:bg-destructive/20 transition-colors"
                      >
                        <XCircle className="w-4 h-4" />
                        Deny
                      </button>
                    </div>
                  )}
                </div>
              )}

              {/* Comments section */}
              <div className="mt-3">
                <button
                  onClick={() => setExpandedId(isExpanded ? null : leave.id)}
                  className="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors"
                >
                  <MessageSquare className="w-3.5 h-3.5" />
                  {leave.comments.length > 0 ? `${leave.comments.length} comment${leave.comments.length !== 1 ? "s" : ""}` : "Add comment"}
                  {isExpanded ? <ChevronUp className="w-3.5 h-3.5" /> : <ChevronDown className="w-3.5 h-3.5" />}
                </button>

                {isExpanded && (
                  <div className="mt-3 space-y-3 animate-in fade-in slide-in-from-top-2 duration-200">
                    {leave.comments.length > 0 && (
                      <div className="space-y-2 max-h-48 overflow-y-auto">
                        {leave.comments.map((c) => (
                          <div key={c.id} className={cn("px-3 py-2 rounded-lg text-sm", c.author === "System" ? "bg-secondary/50 text-muted-foreground italic" : "bg-secondary")}>
                            <div className="flex items-center justify-between mb-0.5">
                              <span className="text-xs font-semibold text-foreground">{c.author}</span>
                              <span className="text-[10px] text-muted-foreground">{c.time}</span>
                            </div>
                            <p className="text-sm text-foreground">{c.text}</p>
                          </div>
                        ))}
                      </div>
                    )}
                    <div className="flex items-center gap-2">
                      <input
                        type="text"
                        value={commentInputs[leave.id] || ""}
                        onChange={(e) => setCommentInputs((prev) => ({ ...prev, [leave.id]: e.target.value }))}
                        onKeyDown={(e) => e.key === "Enter" && addComment(leave.id)}
                        placeholder="Write a comment..."
                        className="flex-1 h-9 px-3 rounded-lg bg-secondary border border-border text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent"
                      />
                      <button
                        onClick={() => addComment(leave.id)}
                        disabled={!commentInputs[leave.id]?.trim()}
                        className="w-9 h-9 flex items-center justify-center rounded-lg bg-accent text-accent-foreground hover:bg-accent/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <Send className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
