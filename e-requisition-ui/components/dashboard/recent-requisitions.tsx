"use client";

import { cn } from "@/lib/utils";
import { ArrowUpRight, Clock, CheckCircle2, XCircle, AlertTriangle, Banknote } from "lucide-react";

const requisitions = [
  {
    reference: "REQ-2026-0247",
    project: "Copperline Client Rollout",
    amount: "ZMW 5,000.00",
    status: "submitted",
    date: "2 hours ago",
    requester: "Jane Employee",
    branch: "Zambia",
  },
  {
    reference: "REQ-2026-0246",
    project: "Warehouse PPE Refresh",
    amount: "ZMW 12,500.00",
    status: "approved",
    date: "5 hours ago",
    requester: "Jane Employee",
    branch: "Zambia",
  },
  {
    reference: "REQ-2026-0245",
    project: "Office Furniture Upgrade",
    amount: "ZAR 45,000.00",
    status: "processing",
    date: "1 day ago",
    requester: "Admin User",
    branch: "South Africa",
  },
  {
    reference: "REQ-2026-0244",
    project: "IT Equipment Purchase",
    amount: "USD 8,200.00",
    status: "denied",
    date: "2 days ago",
    requester: "Jane Employee",
    branch: "Zimbabwe",
  },
  {
    reference: "REQ-2026-0243",
    project: "Operations Internet Upgrade",
    amount: "ZMW 3,200.00",
    status: "closed",
    date: "3 days ago",
    requester: "Jane Employee",
    branch: "Zambia",
  },
];

const statusConfig: Record<string, { icon: React.ElementType; color: string; bg: string; label: string }> = {
  submitted: { icon: Clock, color: "text-warning", bg: "bg-warning/10", label: "Submitted" },
  approved: { icon: CheckCircle2, color: "text-success", bg: "bg-success/10", label: "Approved" },
  processing: { icon: Banknote, color: "text-chart-1", bg: "bg-chart-1/10", label: "Processing" },
  denied: { icon: XCircle, color: "text-destructive", bg: "bg-destructive/10", label: "Denied" },
  closed: { icon: CheckCircle2, color: "text-muted-foreground", bg: "bg-muted/50", label: "Closed" },
};

import React from "react";

export function RecentRequisitions() {
  return (
    <div className="bg-card border border-border rounded-xl p-5 animate-in fade-in slide-in-from-bottom-4 duration-500 delay-200">
      <div className="flex items-center justify-between mb-5">
        <div>
          <h3 className="text-base font-semibold text-foreground">Recent Requisitions</h3>
          <p className="text-sm text-muted-foreground mt-0.5">Latest activity across all branches</p>
        </div>
        <button className="flex items-center gap-1 text-sm text-accent hover:text-accent/80 font-medium transition-colors group">
          View all
          <ArrowUpRight className="w-4 h-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
        </button>
      </div>

      <div className="space-y-3">
        {requisitions.map((req, index) => {
          const status = statusConfig[req.status];
          const StatusIcon = status.icon;

          return (
            <div
              key={req.reference}
              className="group flex items-center justify-between p-3 rounded-lg hover:bg-secondary/50 transition-all duration-200 cursor-pointer animate-in fade-in slide-in-from-left-2"
              style={{ animationDelay: `${(index + 3) * 100}ms`, animationFillMode: "both" }}
            >
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center text-sm font-semibold text-muted-foreground group-hover:bg-accent/10 group-hover:text-accent transition-all duration-200">
                  {req.branch.charAt(0)}
                </div>
                <div>
                  <p className="text-sm font-medium text-foreground">{req.reference}</p>
                  <p className="text-xs text-muted-foreground">{req.project} • {req.date}</p>
                </div>
              </div>

              <div className="flex items-center gap-3">
                <span className="text-sm font-semibold text-foreground hidden sm:block">{req.amount}</span>
                <div className={cn("flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium", status.bg, status.color)}>
                  <StatusIcon className="w-3 h-3" />
                  {status.label}
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
