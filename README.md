> 🌐 **Language:** [🇻🇳 Tiếng Việt](./README_vi.md) · 🇬🇧 English (current)

# S-Cart Multi-Store — One admin, many sales websites

## Introduction
Multi-Store is a plugin that lets a business **run multiple sales websites from a single admin system**. This document is for business owners and operations managers (no technical background needed): after reading it you will understand what Multi-Store is for, the value it brings, how it differs from Multi-Vendor, and how the Free edition differs from Pro so you can pick the right fit.

## 1. What Multi-Store is and the problem it solves
When a business wants **several different stores/websites** — for example one domain per brand, one language/currency per market, or separate wholesale and retail sites — the usual approach is to build several standalone websites. The result: scattered data, many logins to manage, higher maintenance cost, and no single overview.

Multi-Store brings everything into **one place**:

- **Many stores, one admin.** Each store has its own domain, its own interface (template), its own language and currency — yet you manage them all from a single admin panel.
- **Shared platform.** Products, orders, and customers live in one system; no need to rebuild from scratch for each website.
- **Lower cost and effort.** Install once, operate in one place, update in one place — instead of maintaining several separate websites.
- **Grow gradually.** Start with one store and add new ones when needed, without rebuilding the system.

> 👉 Try it live on the demo site: **https://demo.s-cart.org**

## 2. How Multi-Store differs from Multi-Vendor
These are two **different** business models, and they **cannot be installed together** on the same website (the system blocks it to prevent data conflicts). Pick the right model:

| Criteria | 🏢 **Multi-Store** | 🛒 **Multi-Vendor** |
|---|---|---|
| Who owns the goods | **One owner** (your business) | **Many independent vendors** selling together |
| Model | A chain of stores / multiple brands of one owner | An online marketplace |
| Who manages products | You (the system owner) | Each vendor lists their own products |
| Commission / payouts to third parties | None | Yes — commission splits and vendor payouts |
| Best when | You want several websites/domains for **one business** | You want to **invite others** to sell and earn commission |

In short: **Multi-Store = many stores that are all yours; Multi-Vendor = one marketplace for many sellers.** If you need a marketplace model, look at the Multi-Vendor plugin instead of Multi-Store.

## 3. Free vs Pro
Multi-Store comes as a **Free edition** (free, enough to get started) and a **Pro edition** (unlocks every feature for large-scale operations).

| Feature | 🆓 **Free** | ⭐ **Pro** |
|---|:---:|:---:|
| Number of stores | Up to **3** (including the root store) | **Unlimited** |
| Multiple domains, a distinct interface per store | ✅ | ✅ |
| Per-store language & currency | ✅ | ✅ |
| Per-store configuration | ✅ | ✅ |
| Strict domain validation (STRICT) | ✅ | ✅ |
| **Dedicated administrator per store** (logs in on that store's domain) | — | ✅ |
| **Sync products to stores** (publish root products to many stores, keep the source link) | — | ✅ |
| **Unified dashboard** (compare revenue / orders / average order value across stores, export to Excel) | — | ✅ |
| **Product revenue report** across the whole system (with per-store clones) | — | ✅ |
| **Lock / unlock a store** (the platform owner pauses a store) | — | ✅ |

- **Choose Free when:** you are starting out, need up to 3 stores, and manage everything yourself.
- **Upgrade to Pro when:** you need more than 3 stores, want to hand each store to its own administrator, or need consolidated system-wide reports.

🔗 **Learn more & upgrade to Pro:** [gp247.net/en/product/multi-store-pro.html](https://gp247.net/en/product/multi-store-pro.html) · [Tiếng Việt](https://gp247.net/vi/product/multi-store-pro.html)

## 4. Getting products into stores & granting admin rights
Two common workflows when running many stores — the Free edition is enough to start, the advanced parts are Pro:

- **Post a product for a specific store (in Free):** with Multi-Store installed, when creating/editing a product in the root admin you **pick the store** it belongs to — the product shows only in that store.
- **Publish a root product to many stores (Pro):** publish one root product to many stores from a single place (the *"Sync products to stores"* screen), each store getting an independent copy that keeps a source link and can be re-synced.
- **Grant a dedicated administrator per store (Pro):** hand each store to its own administrator (two presets ship: *Store Manager* / *Store Member*); they log in on the store domain, see only their scope, and cannot reach the root admin — **every platform-wide action stays blocked no matter which permissions they hold**.

🔗 The two Pro workflows above: [gp247.net/en/product/multi-store-pro.html](https://gp247.net/en/product/multi-store-pro.html) · [Tiếng Việt](https://gp247.net/vi/product/multi-store-pro.html)

## Conditions & Rules (know before you use it)
- **Cannot be installed alongside Multi-Vendor** — Multi-Store and multi-vendor are different models that share store data in conflicting ways. The system **blocks installation** if a multi-vendor plugin is present; remove the other plugin first, then install.
- **The Free edition is limited to 3 stores** (including the root store) — enough to get started; to add more, upgrade to Pro. This is the line between the two editions, not an error.
- **The root (ROOT) store cannot be locked** — the root store is the foundation of the whole system; locking it would take everything down, so this action is always blocked (locking is a Pro feature).
- **Platform requirements:** requires `gp247/shop` and runs on GP247 core 3.0 or later. This is an S-Cart/GP247 plugin, not a standalone product.

## Q&A
**Q1: Do I need to build a separate website for each store?**

→ No. You install one system; each store is a domain pointing to that same system, all managed together in one admin panel.

**Q2: Can each store have its own interface, language, and currency?**

→ Yes. Each store configures its own template, language, currency, and settings — even though they share one platform.

**Q3: Should I choose Multi-Store or Multi-Vendor?**

→ Choose Multi-Store if all the stores are yours. Choose Multi-Vendor if you want different sellers on a marketplace and you collect commission.

**Q4: How many stores can the Free edition run?**

→ Up to 3 stores, including the root store. If you need more, upgrade to Pro (unlimited).

**Q5: What is the most valuable thing Pro adds?**

→ Per-store administrators, syncing products to many stores, a unified dashboard, system-wide revenue reports, and store locking — built for running many stores for real.

**Q6: If I'm on Free and later upgrade to Pro, do I lose data?**

→ No. Pro extends Free on the same platform; upgrading unlocks more features while your existing store data stays intact.

**Q7: Where can I try it before deciding?**

→ The public demo site: https://demo.s-cart.org

**Q8: Why can't I install it together with a multi-vendor plugin?**

→ The two models share store data differently, and running them together would corrupt data. The system blocks it to protect you; pick just one model that fits.

**Q9: Where do I find detailed installation and configuration?**

→ See the official product page: [gp247.net/en/product/multi-store-pro.html](https://gp247.net/en/product/multi-store-pro.html) (a Vietnamese version is available too).

---

<sub>📅 **Last updated:** 2026-09-02 · ✍️ **Author:** GP247</sub>
