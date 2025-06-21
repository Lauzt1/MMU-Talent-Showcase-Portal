# MMU Talent Showcase Portal

## 📌 Project Overview
The **MMU Talent Showcase Portal** is a web application developed to allow MMU students to showcase their talents through multimedia portfolios. It features user registration, profile management, talent browsing, and an admin panel for system management.

---

## 💻 Technologies Used
- **Frontend**: HTML, CSS, JavaScript  
- **Backend**: PHP 7.4 or higher  
- **Database**: MySQL  
- **Server Environment**: XAMPP (Apache & MySQL)

---

## 📂 Installation & Setup

### 1. Install Requirements
Download and install **XAMPP** from:  
🔗 [https://www.apachefriends.org/download.html](https://www.apachefriends.org/download.html)

### 2. Start Local Server
Open **XAMPP Control Panel** and click `Start` for both **Apache** and **MySQL**.

### 3. Import the Database
1. Open your browser and go to:  
   🔗 `http://localhost/phpmyadmin`
2. Create a new database named:  
   🔸 `mmu_talent_showcase`
3. Click **Import**, select the provided SQL file: mmu_talent_showcase.sql, and import it.
4. You should now see **10 tables** successfully created.

---

## 🌐 Accessing the Application

- 🔹 User Page:  
  `http://localhost/mmu-talent-showcase/index.php`

- 🔹 Admin Page:  
  `http://localhost/mmu-talent-showcase/index.php`  
  (Admin login required)

- 🔹 Database Access:  
  `http://localhost/phpmyadmin`

---

## 👥 User Roles

### 1. Student
- Register and log in
- Edit profile and upload portfolios
- Browse talents in the catalogue
- Rate other users and submit feedback

### 2. Admin
- Manage users, portfolios, announcements, and feedback
- Approve or reject uploaded content
- Handle password reset requests
- Maintain FAQs

---

## 🔐 Admin Access
To access the admin dashboard, use the admin credentials provided during setup. Admin users are identified by a role flag in the database.

---

## 📦 Features

### Student
- Multimedia portfolio upload (image/video/document)
- Talent browsing with category filtering
- Star rating system
- Resource sharing
- Feedback submission

### Admin
- Dashboard with CRUD operations
- Portfolio approval workflow
- FAQ and Announcement Management
- Password reset approval
- User account management

---

## ✅ Future Enhancements (Planned)
- Real-time notifications
- Messaging system between users
- Likes and comments for portfolios
- Advanced search filters
- Live chat support
- Social media sharing
- Analytics dashboard for admin
- Event registration module

---

## 👨‍💻 Authors / Contributors
- **Hoo Enn Xin** (1231302621)
- **Lau Zi Thao** (1211102370)
- **Lim Xin Yuen** (1211108007)
- **Teng Wei Joe** (1211102797)

