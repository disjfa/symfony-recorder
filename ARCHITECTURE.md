# 🏗️ Architecture Overview

## System Design

```mermaid
graph TD
    A["🌐 USER BROWSER"] --> B["📄 HTML + CSS + JavaScript"]
    B --> C["Bootstrap 5 + Stimulus Controller"]
    
    A --> D["🎥 MediaRecorder API"]
    A --> E["📺 getDisplayMedia API"]
    D --> F["Video Recording"]
    E --> G["Screen Capture"]
    
    A -->|HTTP/AJAX| H["🔧 SYMFONY 7.4 BACKEND"]
    
    H --> I["🔀 Request Routing"]
    I --> J["Recording Routes"]
    I --> K["Task Routes"]
    I --> L["API Routes"]
    
    H --> M["🎯 Controllers"]
    M --> N["RecordingController"]
    M --> O["TaskController"]
    
    H --> P["🛠️ Service Layer"]
    P --> Q["File Storage Service"]
    P --> R["Task Management Service"]
    
    H --> S["💾 Data Layer"]
    S --> T["Video Entity + Repository"]
    S --> U["Task Entity + Repository"]
    
    H --> V["🗺️ Doctrine ORM"]
    
    V --> W["🗄️ SQLite Database"]
    
    W --> X["📊 video TABLE"]
    W --> Y["📋 task TABLE"]
    
    X --> X1["id, title, description"]
    X --> X2["file_path, mime_type"]
    X --> X3["duration, file_size"]
    X --> X4["created_at, updated_at"]
    
    Y --> Y1["id, title, description"]
    Y --> Y2["status, priority"]
    Y --> Y3["due_date, video_id FK"]
    Y --> Y4["created_at, updated_at"]
    
    W --> Z["📁 File Storage"]
    Z --> Z1["public/videos/"]
    Z1 --> Z2["video_xxx.webm"]
    Z1 --> Z3["video_yyy.webm"]
    
    style A fill:#e1f5ff
    style H fill:#f3e5f5
    style W fill:#e8f5e9
    style Z fill:#fff3e0
```

## Request/Response Flow

### Recording Upload Flow

```mermaid
sequenceDiagram
    participant User
    participant Browser
    participant MediaRecorder
    participant Backend as Symfony Backend
    participant Database
    participant FileSystem as File Storage
    
    User->>Browser: Click "Start Recording"
    Browser->>MediaRecorder: Call getDisplayMedia()
    MediaRecorder->>User: Permission Prompt
    User->>MediaRecorder: Grant Permission
    User->>MediaRecorder: Select Screen
    MediaRecorder->>Browser: Capture MediaStream
    User->>Browser: Click "Stop Recording"
    Browser->>Browser: Convert to WebM Blob
    Browser->>Browser: Show Preview Modal
    User->>Browser: Click "Upload & Create Task"
    Browser->>Browser: Create FormData with blob
    Browser->>Backend: POST /api/upload-video
    Backend->>FileSystem: Save video file
    Backend->>Database: Create Video entity
    Backend->>Database: Create Task entity (auto)
    Backend->>Database: Persist both
    Backend->>Browser: Return JSON response
    Browser->>Browser: Redirect to /gallery
    Browser->>User: Display Video & Task
```

### Task Management Flow

```mermaid
sequenceDiagram
    participant User
    participant Browser
    participant Controller as TaskController
    participant Form as TaskType Form
    participant Database
    participant Template
    
    User->>Browser: Navigate to /task
    Browser->>Controller: GET /task
    Controller->>Database: Query all tasks
    Database->>Controller: Return tasks
    Controller->>Template: Render task/index.html.twig
    Template->>Browser: Display task list
    Browser->>User: Show tasks
    
    User->>Browser: Click "Edit" on task
    Browser->>Controller: GET /task/{id}/edit
    Controller->>Database: Load task
    Controller->>Form: Create form instance
    Form->>Template: Render edit form
    Template->>Browser: Display edit form
    Browser->>User: Show form
    
    User->>Browser: Submit form
    Browser->>Controller: POST /task/{id}/edit
    Controller->>Form: Handle request
    Form->>Form: Validate input
    Controller->>Database: Update task entity
    Database->>Database: Run PreUpdate callback
    Controller->>Database: Flush changes
    Controller->>Browser: Redirect to task show
    Browser->>User: Confirmation message
```

## Entity Relationships

```mermaid
erDiagram
    VIDEO ||--o| TASK : has
    
    VIDEO {
        uuid id PK "Primary Key"
        string title
        text description
        string file_path
        string mime_type
        integer duration
        integer file_size
        datetime_immutable created_at
        datetime_immutable updated_at
    }
    
    TASK {
        uuid id PK "Primary Key"
        string title
        text description
        string status "pending, in_progress, completed, cancelled"
        string priority "low, medium, high"
        datetime_immutable due_date
        datetime_immutable created_at
        datetime_immutable updated_at
        uuid video_id FK "Foreign Key to Video"
    }
```

### Relationship Rules

- **One Video = One Task** (OneToOne)
- **One Task = One Video** (inverse side)
- **Task MUST have a Video** (Foreign Key NOT NULL)
- **Video CAN have a Task** (nullable inverse side)
- **Cascading Delete**: Deleting a Video also deletes its Task

## Data Flow Diagram

### Creating a Recording

```mermaid
flowchart TD
    A["🎥 Recording Page"]
    B["⚙️ Stimulus Controller<br/>recorder_controller.js"]
    C["📱 Call getDisplayMedia()"]
    D["🔐 Browser Permission<br/>Dialog"]
    E["✅ User Grants<br/>Permission"]
    F["🖥️ Select Screen"]
    G["📹 MediaRecorder<br/>Captures Screen + Audio"]
    H["⏹️ User Clicks Stop"]
    I["🎬 Convert MediaStream<br/>to WebM Blob"]
    J["👁️ Show Preview<br/>Modal"]
    K["📤 User Clicks Upload"]
    L["📦 Create FormData<br/>with Video Blob"]
    M["🌐 POST to<br/>/api/upload-video"]
    N["🎯 RecordingController<br/>::uploadVideo"]
    O["💾 Save File to<br/>public/videos/"]
    P["📊 Create Video<br/>Entity"]
    Q["✅ Create Task<br/>Entity Auto"]
    R["🗄️ Persist to<br/>Database"]
    S["📋 Return JSON<br/>Response"]
    T["↩️ Redirect to<br/>/gallery"]
    U["🎉 Display Video<br/>& Task"]
    
    A --> B
    B --> C
    C --> D
    D --> E
    E --> F
    F --> G
    G --> H
    H --> I
    I --> J
    J --> K
    K --> L
    L --> M
    M --> N
    N --> O
    N --> P
    N --> Q
    P --> R
    Q --> R
    R --> S
    S --> T
    T --> U
    
    style A fill:#e1f5ff
    style B fill:#f3e5f5
    style N fill:#e8f5e9
    style U fill:#c8e6c9
```

## Technology Stack

### Backend Layers

```mermaid
graph TD
    A["HTTP Layer<br/>(Routing, Middleware)"]
    B["🎯 Controller Layer<br/>(Business Logic)"]
    C["RecordingController"]
    D["TaskController"]
    E["🛠️ Service Layer<br/>(Domain Logic)"]
    F["Optional Services"]
    G["💾 Repository Layer<br/>(Data Access)"]
    H["VideoRepository"]
    I["TaskRepository"]
    J["🗺️ ORM Layer<br/>(Doctrine)"]
    K["Entity Mapping"]
    L["Query Building"]
    M["🗄️ Database Layer<br/>(SQLite)"]
    N["Tables"]
    O["Relationships"]
    
    A --> B
    B --> C
    B --> D
    B --> E
    E --> F
    E --> G
    G --> H
    G --> I
    G --> J
    J --> K
    J --> L
    J --> M
    M --> N
    M --> O
    
    style A fill:#e3f2fd
    style B fill:#f3e5f5
    style E fill:#ede7f6
    style G fill:#e8f5e9
    style J fill:#fff3e0
    style M fill:#fce4ec
```

### Frontend Layers

```mermaid
graph TD
    A["👁️ View Layer<br/>(Twig Templates)"]
    B["base.html.twig"]
    C["recording/index.html"]
    D["task/*.html.twig"]
    E["🎨 Styling Layer<br/>(Bootstrap 5 + CSS)"]
    F["app.css"]
    G["Bootstrap CDN"]
    H["⚙️ Stimulus Controllers<br/>(JavaScript)"]
    I["recorder_controller.js"]
    J["🔧 Browser APIs"]
    K["MediaDevices"]
    L["MediaRecorder"]
    M["Fetch API"]
    
    A --> B
    A --> C
    A --> D
    A --> E
    E --> F
    E --> G
    A --> H
    H --> I
    H --> J
    J --> K
    J --> L
    J --> M
    
    style A fill:#e0f2f1
    style E fill:#fff9c4
    style H fill:#f3e5f5
    style J fill:#ffe0b2
```

## File Organization

```
symfony-recorder/
├── src/                          # PHP Source Code
│   ├── Controller/               # Request Handlers
│   │   ├── RecordingController.php
│   │   └── TaskController.php
│   ├── Entity/                   # Database Models
│   │   ├── Video.php
│   │   └── Task.php
│   ├── Form/                     # Symfony Forms
│   │   └── TaskType.php
│   ├── Traits/                   # Reusable Traits
│   │   └── TimestampableTrait.php
│   └── Repository/               # Data Access
│       ├── VideoRepository.php
│       └── TaskRepository.php
│
├── templates/                    # Twig Templates
│   ├── base.html.twig           # Layout
│   ├── home.html.twig           # Landing
│   ├── recording/               # Recording Pages
│   │   ├── index.html.twig
│   │   └── gallery.html.twig
│   └── task/                    # Task Pages
│       ├── index.html.twig
│       ├── show.html.twig
│       ├── edit.html.twig
│       └── components/
│           └── task_list.html.twig
│
├── assets/                       # Frontend Assets
│   ├── app.js                   # Entry Point
│   ├── styles/app.css           # Styling
│   ├── controllers/
│   │   └── recorder_controller.js  # Stimulus
│   └── stimulus_bootstrap.js    # Framework Setup
│
├── public/                       # Web Root
│   ├── index.php                # App Entry
│   └── videos/                  # Video Storage
│
├── config/                       # Configuration
│   ├── routes.yaml
│   ├── routes/routing.controllers.yaml
│   ├── services.yaml
│   └── packages/
│
├── migrations/                   # Database Migrations
│   └── Version20260212195616.php
│
├── var/                          # Runtime Data
│   ├── app.db                   # SQLite Database
│   ├── cache/
│   └── log/
│
└── bin/
    └── console                  # Symfony CLI
```

## Security Architecture

```mermaid
graph TD
    A["HTTPS Layer<br/>(Not in dev, add for production)"]
    
    B["Input Validation"]
    C["File Upload Validation"]
    D["CORS Handling"]
    
    E["CSRF Protection<br/>(Symfony Built-in)"]
    
    F["Authentication<br/>(Not implemented)<br/>(Add if needed)"]
    
    G["Authorization<br/>(Not implemented)<br/>(Add if needed)"]
    
    A --> B
    A --> C
    A --> D
    B --> E
    C --> E
    D --> E
    E --> F
    F --> G
    
    style A fill:#ffccbc
    style E fill:#c8e6c9
    style F fill:#fff9c4
    style G fill:#ffccbc
```

## Deployment Architecture (Future)

```mermaid
graph LR
    A["📚 CDN<br/>CloudFront"]
    B["⚖️ Load<br/>Balancer"]
    C["🖥️ Web Server<br/>Apache/Nginx"]
    D["🚀 Symfony App<br/>PHP-FPM"]
    E["💾 Database<br/>PostgreSQL"]
    F["☁️ Video Storage<br/>AWS S3"]
    G["📨 Message Queue<br/>RabbitMQ"]
    H["🎬 Video Processing"]
    I["📬 Notifications"]
    J["⚙️ Background Jobs"]
    
    A -->|CSS, JS, Icons| B
    B --> C
    C --> D
    D --> E
    D --> F
    D --> G
    G --> H
    G --> I
    G --> J
    
    style A fill:#fff9c4
    style B fill:#ffccbc
    style C fill:#bbdefb
    style D fill:#c8e6c9
    style E fill:#f8bbd0
    style F fill:#b2dfdb
    style G fill:#ffe0b2
```

---

This architecture ensures:
- ✅ Clean separation of concerns
- ✅ Scalability for future growth
- ✅ Security best practices
- ✅ Maintainability and testability
- ✅ Performance optimization paths

