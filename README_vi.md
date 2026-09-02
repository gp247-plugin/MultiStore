> 🌐 **Ngôn ngữ:** 🇻🇳 Tiếng Việt (hiện tại) · [🇬🇧 English](./README.md)

# S-Cart Multi-Store — Một trang quản trị, nhiều website bán hàng

## Giới thiệu
Multi-Store là plugin giúp doanh nghiệp **vận hành nhiều website bán hàng từ một hệ thống quản trị duy nhất**. Tài liệu này dành cho chủ doanh nghiệp và người phụ trách vận hành (không cần rành kỹ thuật): đọc xong bạn sẽ hiểu Multi-Store dùng để làm gì, mang lại giá trị gì, khác Multi-Vendor ra sao, và bản Free khác bản Pro ở những điểm nào để chọn cho đúng nhu cầu.

## 1. Multi-Store là gì và giải quyết việc gì
Khi doanh nghiệp muốn có **nhiều cửa hàng/website khác nhau** — ví dụ mỗi thương hiệu một tên miền, mỗi thị trường một ngôn ngữ/tiền tệ, hay tách website bán sỉ và bán lẻ — cách làm thông thường là dựng nhiều website riêng. Hậu quả: dữ liệu phân tán, phải đăng nhập nhiều nơi, tốn chi phí duy trì và khó nhìn tổng thể.

Multi-Store gộp tất cả về **một chỗ**:

- **Nhiều cửa hàng, một admin.** Mỗi cửa hàng có tên miền riêng, giao diện (template) riêng, ngôn ngữ và tiền tệ riêng — nhưng bạn chỉ quản lý ở một trang quản trị.
- **Dùng chung nền tảng.** Sản phẩm, đơn hàng, khách hàng nằm chung một hệ thống; không phải dựng lại từ đầu cho mỗi website.
- **Tiết kiệm chi phí & công sức.** Một lần cài đặt, một nơi vận hành, một nơi cập nhật — thay vì nuôi nhiều website tách rời.
- **Mở rộng dần.** Bắt đầu với một cửa hàng, thêm cửa hàng mới khi cần mà không phải làm lại hệ thống.

> 👉 Xem thử trực tiếp tại trang demo: **https://demo.s-cart.org**

## 2. Multi-Store khác Multi-Vendor thế nào
Đây là hai mô hình kinh doanh **khác nhau**, và **không cài chung được** trên cùng một website (hệ thống sẽ chặn để tránh xung đột dữ liệu). Hãy chọn đúng mô hình:

| Tiêu chí | 🏢 **Multi-Store** | 🛒 **Multi-Vendor** |
|---|---|---|
| Ai sở hữu hàng hoá | **Một chủ** (doanh nghiệp của bạn) | **Nhiều nhà cung cấp** độc lập cùng bán |
| Mô hình | Chuỗi cửa hàng / nhiều thương hiệu của cùng một chủ | Sàn thương mại điện tử (marketplace) |
| Ai quản lý sản phẩm | Bạn (chủ hệ thống) | Từng nhà cung cấp tự đăng bán |
| Hoa hồng / thanh toán cho bên thứ ba | Không có | Có — chia hoa hồng, đối soát trả tiền cho vendor |
| Phù hợp khi | Bạn muốn nhiều website/tên miền cho **cùng một doanh nghiệp** | Bạn muốn **mời người khác** vào bán và ăn hoa hồng |

Nói ngắn gọn: **Multi-Store = nhiều cửa hàng của chính bạn; Multi-Vendor = một cái chợ cho nhiều người bán.** Nếu bạn cần mô hình marketplace, hãy xem plugin Multi-Vendor thay vì Multi-Store.

## 3. So sánh bản Free và bản Pro
Multi-Store có sẵn **bản Free** (miễn phí, đủ dùng để bắt đầu) và **bản Pro** (mở khoá toàn bộ chức năng cho vận hành quy mô lớn).

| Chức năng | 🆓 **Free** | ⭐ **Pro** |
|---|:---:|:---:|
| Số cửa hàng | Tối đa **3** (tính cả cửa hàng gốc) | **Không giới hạn** |
| Nhiều tên miền, mỗi cửa hàng một giao diện | ✅ | ✅ |
| Ngôn ngữ & tiền tệ riêng theo cửa hàng | ✅ | ✅ |
| Cấu hình riêng cho từng cửa hàng | ✅ | ✅ |
| Kiểm duyệt tên miền nghiêm ngặt (STRICT) | ✅ | ✅ |
| **Quản trị viên riêng cho từng cửa hàng** (đăng nhập trên tên miền cửa hàng đó) | — | ✅ |
| **Đồng bộ sản phẩm sang cửa hàng** (đăng sản phẩm gốc sang nhiều cửa hàng, giữ liên kết nguồn) | — | ✅ |
| **Dashboard hợp nhất** (so sánh doanh thu / số đơn / giá trị đơn TB giữa các cửa hàng, xuất Excel) | — | ✅ |
| **Báo cáo doanh thu sản phẩm** toàn hệ thống (kèm bản sao ở từng cửa hàng) | — | ✅ |
| **Khoá / mở khoá cửa hàng** (chủ sàn tạm ngừng phục vụ một cửa hàng) | — | ✅ |

- **Chọn Free khi:** bạn mới bắt đầu, cần tối đa 3 cửa hàng và tự một mình quản lý tất cả.
- **Nâng lên Pro khi:** bạn cần nhiều hơn 3 cửa hàng, muốn giao mỗi cửa hàng cho một người quản trị riêng, hoặc cần báo cáo tổng hợp toàn hệ thống.

🔗 **Tìm hiểu & nâng cấp bản Pro:** [gp247.net/vi/product/multi-store-pro.html](https://gp247.net/vi/product/multi-store-pro.html) · [English](https://gp247.net/en/product/multi-store-pro.html)

## Điều kiện & ràng buộc (hiểu trước khi dùng)
- **Không cài chung với Multi-Vendor** — Multi-Store và các plugin multi-vendor (MultiVendorPro / MultiVendor / Pmo247) là hai mô hình khác nhau, dùng chung dữ liệu cửa hàng theo cách xung đột nhau. Hệ thống sẽ **chặn cài đặt** nếu website đang có plugin multi-vendor; gỡ plugin kia trước rồi mới cài.
- **Bản Free giới hạn 3 cửa hàng** (tính cả cửa hàng gốc) — đủ để bắt đầu; muốn thêm phải nâng lên Pro. Đây là ranh giới giữa hai bản, không phải lỗi.
- **Không khoá được cửa hàng gốc (ROOT)** — cửa hàng gốc là nền của cả hệ thống; khoá nó sẽ làm sập toàn bộ, nên thao tác này luôn bị chặn (chức năng khoá thuộc bản Pro).
- **Yêu cầu nền tảng:** cần có `gp247/shop` và chạy trên GP247 core 3.0 trở lên. Đây là plugin của S-Cart/GP247, không dùng độc lập.

## Hỏi & Đáp (Q&A)
**Câu 1: Tôi có cần dựng nhiều website riêng cho từng cửa hàng không?**

→ Không. Bạn chỉ cài một hệ thống; mỗi cửa hàng là một tên miền trỏ về cùng hệ thống đó và được quản lý chung trong một trang admin.

**Câu 2: Mỗi cửa hàng có được giao diện, ngôn ngữ, tiền tệ riêng không?**

→ Có. Mỗi cửa hàng cấu hình template, ngôn ngữ, tiền tệ và các thiết lập riêng — dù dùng chung nền tảng.

**Câu 3: Tôi nên chọn Multi-Store hay Multi-Vendor?**

→ Chọn Multi-Store nếu tất cả cửa hàng đều của bạn. Chọn Multi-Vendor nếu bạn muốn nhiều người bán khác nhau cùng lên sàn và bạn thu hoa hồng.

**Câu 4: Bản Free dùng được bao nhiêu cửa hàng?**

→ Tối đa 3 cửa hàng, tính cả cửa hàng gốc. Cần nhiều hơn thì nâng lên Pro (không giới hạn).

**Câu 5: Bản Pro thêm được gì đáng giá nhất?**

→ Quản trị viên riêng theo cửa hàng, đồng bộ sản phẩm sang nhiều cửa hàng, dashboard hợp nhất, báo cáo doanh thu toàn hệ và khoá cửa hàng — dành cho vận hành nhiều cửa hàng thật sự.

**Câu 6: Đang dùng Free, sau này nâng lên Pro có mất dữ liệu không?**

→ Không. Pro là bản mở rộng của Free trên cùng nền tảng; nâng cấp để mở khoá thêm chức năng, dữ liệu cửa hàng hiện có được giữ nguyên.

**Câu 7: Tôi muốn xem thử trước khi quyết định thì xem ở đâu?**

→ Trang demo công khai: https://demo.s-cart.org

**Câu 8: Vì sao không cài được cùng plugin multi-vendor?**

→ Hai mô hình dùng chung dữ liệu cửa hàng theo cách khác nhau, chạy chung sẽ hỏng dữ liệu. Hệ thống chủ động chặn để bảo vệ bạn; chỉ chọn một mô hình phù hợp.

**Câu 9: Cài đặt và cấu hình chi tiết xem ở đâu?**

→ Xem trang sản phẩm chính thức: [gp247.net/vi/product/multi-store-pro.html](https://gp247.net/vi/product/multi-store-pro.html) (có bản tiếng Anh tương ứng).

---

<sub>📅 **Cập nhật lần cuối:** 2026-09-02 · ✍️ **Tác giả (Author):** GP247</sub>
