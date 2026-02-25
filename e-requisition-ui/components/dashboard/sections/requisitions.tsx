"use client";

import { useState } from "react";
import { cn } from "@/lib/utils";
import { Plus, Search, Filter, Clock, CheckCircle2, XCircle, Banknote, FileCheck, Archive } from "lucide-react";
import { RequisitionForm } from "@/components/dashboard/modals/requisition-form";

const allRequisitions = [
  { ref: "REQ-2026-0247", type: "Cash", project: "Copperline Client Rollout", category: "Travel", branch: "Zambia", amount: "ZMW 5,000.00", status: "submitted", date: "20 Feb 2026", requester: "Jane Employee" },
  { ref: "REQ-2026-0246", type: "Purchase", project: "Warehouse PPE Refresh", category: "Procurement", branch: "Zambia", amount: "ZMW 12,500.00", status: "stage1_approved", date: "19 Feb 2026", requester: "Jane Employee" },
  { ref: "REQ-2026-0245", type: "Cash", project: "Office Furniture Upgrade", category: "Office Supplies", branch: "South Africa", amount: "ZAR 45,000.00", status: "approved", date: "18 Feb 2026", requester: "Admin User" },
  { ref: "REQ-2026-0244", type: "Cash", project: "IT Equipment Purchase", category: "IT & Software", branch: "Zimbabwe", amount: "USD 8,200.00", status: "processing", date: "17 Feb 2026", requester: "Jane Employee" },
  { ref: "REQ-2026-0243", type: "Cash", project: "Operations Internet Upgrade", category: "Operations", branch: "Zambia", amount: "ZMW 3,200.00", status: "paid", date: "15 Feb 2026", requester: "Jane Employee" },
  { ref: "REQ-2026-0242", type: "Purchase", project: "Marketing Materials", category: "Marketing", branch: "Eswatini", amount: "SZL 18,000.00", status: "fulfilled", date: "14 Feb 2026", requester: "Admin User" },
  { ref: "REQ-2026-0241", type: "Cash", project: "Vehicle Maintenance", category: "Fleet & Transport", branch: "South Africa", amount: "ZAR 22,500.00", status: "closed", date: "12 Feb 2026", requester: "Jane Employee" },
  { ref: "REQ-2026-0240", type: "Cash", project: "Training Programme", category: "Training", branch: "Zimbabwe", amount: "USD 3,500.00", status: "denied", date: "10 Feb 2026", requester: "Admin User" },
  { ref: "REQ-2026-0239", type: "Cash", project: "Security System Upgrade", category: "Operations", branch: "Zambia", amount: "ZMW 28,000.00", status: "draft", date: "9 Feb 2026", requester: "Jane Employee" },
];

const statusConfig: Record<string, { color: string; bg: string; label: string }> = {
  draft: { color: "text-muted-foreground", bg: "bg-muted/50", label: "Draft" },
  submitted: { color: "text-warning", bg: "bg-warning/10", label: "Submitted" },
  stage1_approved: { color: "text-chart-1", bg: "bg-chart-1/10", label: "Stage 1" },
  approved: { color: "text-success", bg: "bg-success/10", label: "Approved" },
  processing: { color: "text-chart-1", bg: "bg-chart-1/10", label: "Processing" },
  paid: { color: "text-chart-2", bg: "bg-chart-2/10", label: "Paid" },
  fulfilled: { color: "text-success", bg: "bg-success/10", label: "Fulfilled" },
  closed: { color: "text-muted-foreground", bg: "bg-muted/50", label: "Closed" },
  denied: { color: "text-destructive", bg: "bg-destructive/10", label: "Denied" },
};

export function RequisitionsSection() {
  const [formOpen, setFormOpen] = useState(false);

  return (
    <div className="space-y-6">
      <RequisitionForm open={formOpen} onClose={() => setFormOpen(false)} />

      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-semibold text-foreground">All Requisitions</h2>
          <p className="text-sm text-muted-foreground mt-0.5">Manage cash and purchase requisitions</p>
        </div>
        <button
          onClick={() => setFormOpen(true)}
          className="flex items-center gap-2 px-4 py-2.5 bg-accent text-accent-foreground rounded-lg text-sm font-medium hover:bg-accent/90 transition-colors"
        >
          <Plus className="w-4 h-4" />
          New Requisition
        </button>
      </div>

      <div className="flex items-center gap-3">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <input
            type="text"
            placeholder="Search requisitions..."
            className="w-full h-10 pl-9 pr-4 rounded-lg bg-card border border-border text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent transition-all"
          />
        </div>
        <button className="flex items-center gap-2 px-3 py-2.5 rounded-lg bg-card border border-border text-sm text-muted-foreground hover:text-foreground transition-colors">
          <Filter className="w-4 h-4" />
          Filters
        </button>
      </div>

      <div className="bg-card border border-border rounded-xl overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border">
                <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Reference</th>
                <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Type</th>
                <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Project</th>
                <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Branch</th>
                <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Amount</th>
                <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {allRequisitions.map((req, index) => {
                const status = statusConfig[req.status];
                return (
                  <tr
                    key={req.ref}
                    className="hover:bg-secondary/30 transition-colors cursor-pointer animate-in fade-in slide-in-from-bottom-2"
                    style={{ animationDelay: `${index * 50}ms`, animationFillMode: "both" }}
                  >
                    <td className="px-4 py-3 font-medium text-foreground">{req.ref}</td>
                    <td className="px-4 py-3 text-muted-foreground">{req.type}</td>
                    <td className="px-4 py-3 text-foreground">{req.project}</td>
                    <td className="px-4 py-3 text-muted-foreground">{req.branch}</td>
                    <td className="px-4 py-3 font-semibold text-foreground">{req.amount}</td>
                    <td className="px-4 py-3">
                      <span className={cn("inline-flex items-center px-2 py-1 rounded-md text-xs font-medium", status.bg, status.color)}>
                        {status.label}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-muted-foreground">{req.date}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
