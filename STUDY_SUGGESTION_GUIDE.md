# Hướng dẫn sử dụng tính năng "Gợi ý học tập"

## 🎯 **Tính năng mới: Gợi ý học tập**

Tính năng này cho phép AI tự động tạo gợi ý học tập dựa trên ngành học đã được phân tích trước đó.

## 🚀 **Cách sử dụng:**

### 1. **Truy cập trang "Lịch sử phân tích"**
- Đăng nhập vào hệ thống
- Click vào "Lịch sử phân tích" từ dashboard

### 2. **Chọn profile để gợi ý học tập**
- Tìm profile phân tích mà bạn muốn nhận gợi ý
- Click nút **"Gợi ý học"** (màu xanh lá)

### 3. **Xem kết quả gợi ý**
Hệ thống sẽ hiển thị:
- **Môn học cần tập trung** với độ khó
- **Kỹ năng cần cải thiện** với phương pháp cụ thể

## 🔧 **Cách hoạt động:**

### **Logic tự động:**
1. **Lấy ngành học** từ AI recommendations đã phân tích
2. **Nếu không có recommendations** → Dựa vào thông tin profile:
   - **Tech Interest ≥ 8** → Ứng dụng phần mềm
   - **Creativity ≥ 8** → Thiết kế đồ họa  
   - **Communication ≥ 8** → Quản trị kinh doanh
   - **Tech Interest ≥ 7** → Công nghệ thông tin
   - **Default** → Quản trị kinh doanh

### **AI Integration:**
- **Prompt tối ưu** để AI trả về JSON đúng format
- **Fallback system** khi AI không hoạt động
- **Error handling** và thông báo lỗi

## 📊 **Cấu trúc dữ liệu:**

### **Môn học cần tập trung:**
```json
{
  "subject": "Tên môn học",
  "reason": "Lý do cần tập trung", 
  "difficulty": "Độ khó (1-10)"
}
```

### **Kỹ năng cần cải thiện:**
```json
{
  "skill": "Tên kỹ năng",
  "current_level": "Mức độ hiện tại",
  "target_level": "Mức độ mục tiêu",
  "improvement_method": "Cách cải thiện"
}
```

## 🎨 **Giao diện:**

### **Nút "Gợi ý học":**
- **Màu xanh lá** với icon graduation cap
- **Vị trí** bên cạnh nút "Xem chi tiết"
- **Không cần dropdown** phức tạp

### **Hiển thị kết quả:**
- **Card riêng biệt** với header và nút đóng
- **Grid layout** cho môn học cần tập trung
- **Timeline view** cho kỹ năng cần cải thiện
- **Responsive design** cho mobile

## 🧪 **Testing:**

### **File test:**
- `test_study_suggestion.php` - Test các function
- `test_connection.php` - Test kết nối AI
- `debug_ai.php` - Debug AI response

### **Cách test:**
1. Chạy `test_study_suggestion.php` để kiểm tra logic
2. Truy cập trang web và thử tính năng
3. Kiểm tra console để xem debug info

## 🔄 **Workflow hoàn chỉnh:**

1. **User nhập thông tin** → AI phân tích → Lưu recommendations
2. **User click "Gợi ý học"** → Lấy ngành từ recommendations
3. **AI tạo gợi ý học tập** → Hiển thị môn học và kỹ năng
4. **User xem kết quả** → Có thể đóng hoặc tạo gợi ý mới

## ✅ **Lợi ích:**

- **Tự động hóa** - Không cần chọn ngành thủ công
- **Cá nhân hóa** - Dựa trên phân tích AI trước đó
- **Chi tiết** - Cung cấp môn học và kỹ năng cụ thể
- **Dễ sử dụng** - Chỉ cần 1 click
- **Robust** - Có fallback khi AI lỗi

## 🐛 **Troubleshooting:**

### **Nếu không hiển thị gợi ý:**
1. Kiểm tra kết nối AI
2. Xem debug info trong console
3. Thử lại với profile khác

### **Nếu AI không trả về JSON:**
- Hệ thống sẽ sử dụng fallback suggestions
- Vẫn hiển thị gợi ý hữu ích

### **Nếu không có AI recommendations:**
- Hệ thống sẽ dựa vào thông tin profile
- Tự động gợi ý ngành phù hợp
