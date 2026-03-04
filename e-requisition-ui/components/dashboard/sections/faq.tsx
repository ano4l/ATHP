"use client";

import { useState } from "react";
import { cn } from "@/lib/utils";
import { ChevronDown, HelpCircle, BookOpen } from "lucide-react";

interface FaqItem {
  question: string;
  answer: string;
  category: string;
}

const FAQ_ITEMS: FaqItem[] = [
  {
    category: "Getting Started",
    question: "How do I create a new requisition?",
    answer: "Navigate to the Requisitions section and click 'New Requisition'. Fill in all required fields across the four steps: Project Details, Amount & Details, Attachments, and Review. You can save as a draft at the review step or submit immediately.",
  },
  {
    category: "Getting Started",
    question: "What is the difference between a draft and a submitted requisition?",
    answer: "A draft requisition is saved but not yet sent for approval. You can edit and update drafts at any time. Once you submit a requisition, it enters the approval workflow and a reference number is generated.",
  },
  {
    category: "Approvals",
    question: "How does the two-stage approval work?",
    answer: "Requisitions below the threshold (default: 10,000) require only Stage 1 approval from the Operations Director. Requisitions at or above the threshold require both Stage 1 and Stage 2 (Managing Director) approval before they can be processed.",
  },
  {
    category: "Approvals",
    question: "What happens if my requisition is denied?",
    answer: "You will receive a notification with the denial reason. The requisition status changes to 'Denied' and no further processing occurs. You may create a new requisition addressing the feedback provided.",
  },
  {
    category: "Approvals",
    question: "What does 'Modification Requested' mean?",
    answer: "The approver has requested changes to your requisition before they can approve it. Check the comments on your requisition for specific instructions, make the necessary changes, and resubmit.",
  },
  {
    category: "Attachments",
    question: "What file types and sizes are supported?",
    answer: "You can upload PDF, Word documents (.doc, .docx), Excel spreadsheets (.xls, .xlsx), and images (.jpg, .jpeg, .png). Each file must be under 10MB. Categories like Procurement, Materials, and Emergency require supporting documents.",
  },
  {
    category: "Attachments",
    question: "What is a 'Basic Requisition' flag?",
    answer: "When you upload two or more attachments (e.g., multiple quotations for comparison), you can flag the requisition as a Basic Requisition. This signals to approvers that the request includes competitive quotes for evaluation.",
  },
  {
    category: "Workflow",
    question: "What are the requisition statuses?",
    answer: "Draft → Submitted → Stage 1 Approved → Approved (Final) → Processing → Paid → Fulfilled → Closed. A requisition can also be Denied or have a Modification Requested at the approval stages.",
  },
  {
    category: "Workflow",
    question: "How do I track my requisition's progress?",
    answer: "Go to the Requisitions section to see all your requisitions with their current status. The Audit Trail section provides a detailed history of all actions taken on every requisition.",
  },
  {
    category: "Reports",
    question: "What reports are available?",
    answer: "The Reports section provides spend analytics by branch and category, requisition trends over time, approval rates, and total spend summaries. Data is pulled in real time from the system.",
  },
  {
    category: "General",
    question: "Which currencies are supported?",
    answer: "The system supports ZAR (South Africa), ZMW (Zambia), SZL (Eswatini), and USD (Zimbabwe). The currency is automatically set based on the branch you select.",
  },
  {
    category: "General",
    question: "How do I receive notifications?",
    answer: "In-app notifications are sent automatically at key workflow stages: submission, approval, denial, modification requests, processing, and attachment uploads. Check the Notifications section in the sidebar.",
  },
];

const CATEGORIES = [...new Set(FAQ_ITEMS.map(f => f.category))];

export function FaqSection() {
  const [openIndex, setOpenIndex] = useState<number | null>(null);
  const [activeCategory, setActiveCategory] = useState("all");

  const filtered = activeCategory === "all"
    ? FAQ_ITEMS
    : FAQ_ITEMS.filter(f => f.category === activeCategory);

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <div className="w-10 h-10 rounded-lg bg-accent/10 flex items-center justify-center">
          <BookOpen className="w-5 h-5 text-accent" />
        </div>
        <div>
          <h2 className="text-lg font-semibold text-foreground">Help & Guidance</h2>
          <p className="text-sm text-muted-foreground mt-0.5">Frequently asked questions about the e-Requisition system</p>
        </div>
      </div>

      <div className="flex items-center gap-2 flex-wrap">
        <button
          onClick={() => setActiveCategory("all")}
          className={cn(
            "px-3 py-1.5 rounded-lg text-xs font-medium transition-colors",
            activeCategory === "all"
              ? "bg-accent text-accent-foreground"
              : "bg-secondary text-muted-foreground hover:text-foreground"
          )}
        >
          All
        </button>
        {CATEGORIES.map(cat => (
          <button
            key={cat}
            onClick={() => setActiveCategory(cat)}
            className={cn(
              "px-3 py-1.5 rounded-lg text-xs font-medium transition-colors",
              activeCategory === cat
                ? "bg-accent text-accent-foreground"
                : "bg-secondary text-muted-foreground hover:text-foreground"
            )}
          >
            {cat}
          </button>
        ))}
      </div>

      <div className="space-y-2">
        {filtered.map((item, index) => {
          const isOpen = openIndex === index;
          return (
            <div
              key={index}
              className="bg-card border border-border rounded-xl overflow-hidden animate-in fade-in slide-in-from-bottom-2"
              style={{ animationDelay: `${index * 40}ms`, animationFillMode: "both" }}
            >
              <button
                onClick={() => setOpenIndex(isOpen ? null : index)}
                className="w-full flex items-center justify-between p-4 text-left hover:bg-secondary/30 transition-colors"
              >
                <div className="flex items-center gap-3">
                  <HelpCircle className="w-4 h-4 text-accent shrink-0" />
                  <span className="text-sm font-medium text-foreground">{item.question}</span>
                </div>
                <ChevronDown className={cn("w-4 h-4 text-muted-foreground shrink-0 transition-transform duration-200", isOpen && "rotate-180")} />
              </button>
              {isOpen && (
                <div className="px-4 pb-4 pl-11">
                  <p className="text-sm text-muted-foreground leading-relaxed">{item.answer}</p>
                  <span className="inline-block mt-2 px-2 py-0.5 rounded-md bg-secondary text-[10px] font-medium text-muted-foreground">{item.category}</span>
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
