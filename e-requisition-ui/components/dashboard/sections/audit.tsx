"use client";

import { cn } from "@/lib/utils";
import { FileText, CheckCircle2, XCircle, Edit, Send, CreditCard, Package, Lock } from "lucide-react";

const auditEvents = [
  { id: 1, action: "requisition.created", entity: "REQ-2026-0247", actor: "Jane Employee", time: "Today 10:32 AM", icon: FileText, color: "text-chart-1" },
  { id: 2, action: "requisition.submitted", entity: "REQ-2026-0247", actor: "Jane Employee", time: "Today 10:35 AM", icon: Send, color: "text-warning" },
  { id: 3, action: "requisition.stage1_approved", entity: "REQ-2026-0246", actor: "Admin User", time: "Today 09:15 AM", icon: CheckCircle2, color: "text-success" },
  { id: 4, action: "requisition.stage2_approved", entity: "REQ-2026-0246", actor: "Admin User", time: "Today 09:18 AM", icon: CheckCircle2, color: "text-success" },
  { id: 5, action: "requisition.processing", entity: "REQ-2026-0245", actor: "Admin User", time: "Yesterday 4:30 PM", icon: CreditCard, color: "text-chart-1" },
  { id: 6, action: "requisition.paid", entity: "REQ-2026-0243", actor: "Admin User", time: "Yesterday 2:10 PM", icon: CreditCard, color: "text-chart-2" },
  { id: 7, action: "requisition.fulfilled", entity: "REQ-2026-0242", actor: "Jane Employee", time: "Yesterday 11:45 AM", icon: Package, color: "text-success" },
  { id: 8, action: "requisition.denied", entity: "REQ-2026-0240", actor: "Admin User", time: "20 Feb 2026 3:20 PM", icon: XCircle, color: "text-destructive" },
  { id: 9, action: "requisition.closed", entity: "REQ-2026-0241", actor: "Admin User", time: "20 Feb 2026 1:00 PM", icon: Lock, color: "text-muted-foreground" },
  { id: 10, action: "requisition.updated", entity: "REQ-2026-0239", actor: "Jane Employee", time: "19 Feb 2026 9:00 AM", icon: Edit, color: "text-chart-3" },
];

function formatAction(action: string): string {
  const parts = action.split(".");
  const verb = parts[parts.length - 1].replace(/_/g, " ");
  return verb.charAt(0).toUpperCase() + verb.slice(1);
}

export function AuditSection() {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-foreground">Audit Trail</h2>
        <p className="text-sm text-muted-foreground mt-0.5">Complete history of all system actions</p>
      </div>

      <div className="bg-card border border-border rounded-xl overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border">
                <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Action</th>
                <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Entity</th>
                <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Actor</th>
                <th className="text-left px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wider">Timestamp</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {auditEvents.map((event, index) => {
                const Icon = event.icon;
                return (
                  <tr
                    key={event.id}
                    className="hover:bg-secondary/30 transition-colors animate-in fade-in slide-in-from-bottom-2"
                    style={{ animationDelay: `${index * 50}ms`, animationFillMode: "both" }}
                  >
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        <Icon className={cn("w-4 h-4", event.color)} />
                        <span className="font-medium text-foreground">{formatAction(event.action)}</span>
                      </div>
                    </td>
                    <td className="px-4 py-3 font-mono text-sm text-foreground">{event.entity}</td>
                    <td className="px-4 py-3 text-muted-foreground">{event.actor}</td>
                    <td className="px-4 py-3 text-muted-foreground">{event.time}</td>
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
