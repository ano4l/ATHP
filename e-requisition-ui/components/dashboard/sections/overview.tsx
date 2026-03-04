"use client";

import { useState, useEffect, useCallback } from "react";
import { RequisitionForm } from "@/components/dashboard/modals/requisition-form";
import type { Section } from "@/app/page";
import { Clock, FileText, TrendingUp, Plus, AlertTriangle, Bell, Loader2 } from "lucide-react";
import { getDashboard, getRequisitions } from "@/lib/api";

interface OverviewProps {
  onNavigate: (section: Section) => void;
}

interface DashboardData {
  total_requisitions: number;
  pending_approvals: number;
  avg_turnaround_hours: number | null;
  unread_notifications: number;
}

interface RecentReq {
  id: number;
  reference_no: string;
  project_name: string;
  status: string;
  currency: string;
  amount: string;
  created_at: string;
}

const statusColors: Record<string, string> = {
  draft: "text-muted-foreground", submitted: "text-warning", stage1_approved: "text-chart-1",
  approved: "text-success", processing: "text-chart-1", paid: "text-chart-2",
  fulfilled: "text-success", closed: "text-muted-foreground", denied: "text-destructive",
};

export function OverviewSection({ onNavigate }: OverviewProps) {
  const [reqFormOpen, setReqFormOpen] = useState(false);
  const [data, setData] = useState<DashboardData | null>(null);
  const [recent, setRecent] = useState<RecentReq[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [dash, reqs] = await Promise.all([getDashboard(), getRequisitions(undefined, 1)]);
      setData(dash);
      setRecent((reqs.data ?? []).slice(0, 5));
    } catch (e) {
      console.error("Dashboard load failed", e);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);

  if (loading) {
    return <div className="flex items-center justify-center py-24"><Loader2 className="w-6 h-6 animate-spin text-muted-foreground" /></div>;
  }

  return (
    <div className="space-y-6">
      <RequisitionForm open={reqFormOpen} onClose={() => { setReqFormOpen(false); load(); }} />
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { title: "Total Requisitions", value: String(data?.total_requisitions ?? 0), icon: FileText, color: "text-chart-2" },
          { title: "Pending Approvals", value: String(data?.pending_approvals ?? 0), icon: Clock, color: "text-warning" },
          { title: "Avg Turnaround", value: data?.avg_turnaround_hours != null ? `${data.avg_turnaround_hours}h` : "—", icon: TrendingUp, color: "text-success" },
          { title: "Unread Notifications", value: String(data?.unread_notifications ?? 0), icon: Bell, color: "text-chart-1" },
        ].map((m, i) => (
          <div key={m.title} className="bg-card border border-border rounded-xl p-5 animate-in fade-in slide-in-from-bottom-4" style={{ animationDelay: `${i * 80}ms`, animationFillMode: "both" }}>
            <div className="flex items-center gap-3 mb-2">
              <div className="w-9 h-9 rounded-lg bg-secondary flex items-center justify-center">
                <m.icon className={`w-4 h-4 ${m.color}`} />
              </div>
              <span className="text-sm text-muted-foreground">{m.title}</span>
            </div>
            <span className="text-2xl font-bold text-foreground">{m.value}</span>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2">
          <div className="bg-card border border-border rounded-xl p-5">
            <h3 className="text-base font-semibold text-foreground mb-4">Recent Requisitions</h3>
            {recent.length === 0 ? (
              <p className="text-sm text-muted-foreground text-center py-8">No requisitions yet.</p>
            ) : (
              <div className="space-y-3">
                {recent.map(r => (
                  <div key={r.id} className="flex items-center justify-between p-3 rounded-lg hover:bg-secondary/50 transition-colors">
                    <div>
                      <p className="text-sm font-medium text-foreground">{r.reference_no}</p>
                      <p className="text-xs text-muted-foreground">{r.project_name}</p>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-semibold text-foreground">{r.currency} {Number(r.amount).toLocaleString("en", { minimumFractionDigits: 2 })}</p>
                      <p className={`text-xs font-medium capitalize ${statusColors[r.status] ?? "text-muted-foreground"}`}>{r.status.replace(/_/g, " ")}</p>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
        <div className="space-y-4">
          <div className="bg-card border border-border rounded-xl p-5">
            <h3 className="text-base font-semibold text-foreground mb-3">Quick Actions</h3>
            <div className="space-y-2">
              {[
                { label: "New Requisition", icon: Plus, color: "text-chart-2", action: () => setReqFormOpen(true) },
                { label: `Pending Approvals (${data?.pending_approvals ?? 0})`, icon: AlertTriangle, color: "text-warning", action: () => onNavigate("approvals") },
                { label: "View Reports", icon: TrendingUp, color: "text-muted-foreground", action: () => onNavigate("reports") },
                { label: `Notifications (${data?.unread_notifications ?? 0})`, icon: Bell, color: "text-chart-1", action: () => onNavigate("notifications") },
              ].map((item) => (
                <button
                  key={item.label}
                  onClick={item.action}
                  className="w-full flex items-center gap-3 p-3 rounded-lg hover:bg-secondary/50 transition-all duration-200 group"
                >
                  <div className="w-9 h-9 rounded-lg bg-secondary flex items-center justify-center group-hover:bg-accent/10 transition-colors">
                    <item.icon className={`w-4 h-4 ${item.color}`} />
                  </div>
                  <span className="text-sm font-medium text-foreground">{item.label}</span>
                </button>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
