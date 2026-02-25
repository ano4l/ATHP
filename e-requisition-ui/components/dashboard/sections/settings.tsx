"use client";

import { User, Building2, Shield, Bell, Palette } from "lucide-react";

export function SettingsSection() {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-foreground">Settings</h2>
        <p className="text-sm text-muted-foreground mt-0.5">Manage your account and preferences</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-card border border-border rounded-xl p-6">
            <div className="flex items-center gap-3 mb-6">
              <div className="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center">
                <User className="w-5 h-5 text-muted-foreground" />
              </div>
              <div>
                <h3 className="text-base font-semibold text-foreground">Profile</h3>
                <p className="text-sm text-muted-foreground">Your personal information</p>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-muted-foreground mb-1.5">Full Name</label>
                <input type="text" defaultValue="Admin User" className="w-full h-10 px-3 rounded-lg bg-secondary border border-border text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent" />
              </div>
              <div>
                <label className="block text-sm font-medium text-muted-foreground mb-1.5">Email</label>
                <input type="email" defaultValue="admin@acetech.com" className="w-full h-10 px-3 rounded-lg bg-secondary border border-border text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent" />
              </div>
              <div>
                <label className="block text-sm font-medium text-muted-foreground mb-1.5">Role</label>
                <input type="text" defaultValue="Administrator" readOnly className="w-full h-10 px-3 rounded-lg bg-secondary/50 border border-border text-sm text-muted-foreground cursor-not-allowed" />
              </div>
              <div>
                <label className="block text-sm font-medium text-muted-foreground mb-1.5">Branch</label>
                <input type="text" defaultValue="All Branches" readOnly className="w-full h-10 px-3 rounded-lg bg-secondary/50 border border-border text-sm text-muted-foreground cursor-not-allowed" />
              </div>
            </div>

            <div className="mt-4 flex justify-end">
              <button className="px-4 py-2 bg-accent text-accent-foreground rounded-lg text-sm font-medium hover:bg-accent/90 transition-colors">
                Save Changes
              </button>
            </div>
          </div>

          <div className="bg-card border border-border rounded-xl p-6">
            <div className="flex items-center gap-3 mb-6">
              <div className="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center">
                <Shield className="w-5 h-5 text-muted-foreground" />
              </div>
              <div>
                <h3 className="text-base font-semibold text-foreground">Workflow Configuration</h3>
                <p className="text-sm text-muted-foreground">Approval thresholds and rules</p>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-muted-foreground mb-1.5">Stage 2 Threshold</label>
                <input type="text" defaultValue="10,000" className="w-full h-10 px-3 rounded-lg bg-secondary border border-border text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent" />
              </div>
              <div>
                <label className="block text-sm font-medium text-muted-foreground mb-1.5">Duplicate Lookback (days)</label>
                <input type="text" defaultValue="30" className="w-full h-10 px-3 rounded-lg bg-secondary border border-border text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent" />
              </div>
              <div>
                <label className="block text-sm font-medium text-muted-foreground mb-1.5">Max Attachment Size (MB)</label>
                <input type="text" defaultValue="10" className="w-full h-10 px-3 rounded-lg bg-secondary border border-border text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring/20 focus:border-accent" />
              </div>
            </div>
          </div>
        </div>

        <div className="space-y-6">
          <div className="bg-card border border-border rounded-xl p-6">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center">
                <Bell className="w-5 h-5 text-muted-foreground" />
              </div>
              <h3 className="text-base font-semibold text-foreground">Notifications</h3>
            </div>
            <div className="space-y-3">
              {["New submissions", "Approval requests", "Status changes", "Finance updates"].map((item) => (
                <label key={item} className="flex items-center justify-between cursor-pointer group">
                  <span className="text-sm text-foreground">{item}</span>
                  <div className="w-10 h-6 rounded-full bg-accent/80 flex items-center px-0.5 transition-colors">
                    <div className="w-5 h-5 rounded-full bg-accent-foreground translate-x-4 transition-transform" />
                  </div>
                </label>
              ))}
            </div>
          </div>

          <div className="bg-card border border-border rounded-xl p-6">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center">
                <Building2 className="w-5 h-5 text-muted-foreground" />
              </div>
              <h3 className="text-base font-semibold text-foreground">Branches</h3>
            </div>
            <div className="space-y-2">
              {["Zambia (ZMW)", "South Africa (ZAR)", "Zimbabwe (USD)", "Eswatini (SZL)"].map((branch) => (
                <div key={branch} className="flex items-center gap-2 px-3 py-2 rounded-lg bg-secondary/50 text-sm text-foreground">
                  <div className="w-2 h-2 rounded-full bg-accent" />
                  {branch}
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
