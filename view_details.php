<!-- Navigation Bar -->
<nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col items-center h-auto py-4">
            <!-- Enhanced Logo -->
            <div class="logo-container mb-4">
                <div class="text-4xl font-bold logo-text">Package Details</div>
                <div class="logo-underline"></div>
            </div>
            
            <!-- Enhanced Navigation -->
            <div class="hidden md:flex space-x-6 nav-links-container">
                <a href="home.php" class="nav-link">
                    <i class="fas fa-home mr-2"></i> Home
                </a>
                <a href="userpackages.php" class="nav-link">
                    <i class="fas fa-box mr-2"></i> Packages
                </a>
                <a href="my_bookings.php" class="nav-link">
                    <i class="fas fa-bookmark mr-2"></i> My Bookings
                </a>
                <a href="home.php#about" class="nav-link">
                    <i class="fas fa-info-circle mr-2"></i> About Us
                </a>
                <a href="home.php#contact" class="nav-link">
                    <i class="fas fa-envelope mr-2"></i> Contact
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Add these styles -->
<style>
nav {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
    margin-bottom: 2rem;
    padding: 0.5rem 2rem;
    border-bottom: 1px solid rgba(229, 231, 235, 0.5);
}

.logo-container {
    position: relative;
    padding: 0.5rem 0;
    transform-style: preserve-3d;
    perspective: 1000px;
}

.logo-text {
    background: linear-gradient(135deg, #8b5cf6, #6d28d9, #5b21b6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    letter-spacing: -1px;
    animation: glow 3s ease-in-out infinite alternate;
    position: relative;
}

.logo-underline {
    position: absolute;
    bottom: -4px;
    left: 50%;
    transform: translateX(-50%);
    width: 80%;
    height: 3px;
    background: linear-gradient(90deg, transparent, #8b5cf6, #6d28d9, #5b21b6, transparent);
    border-radius: 2px;
    animation: shimmer 2s infinite;
}

.nav-links-container {
    background: rgba(243, 244, 246, 0.7);
    padding: 0.75rem;
    border-radius: 1rem;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
    transform-style: preserve-3d;
}

.nav-link {
    color: #4b5563;
    text-decoration: none;
    font-weight: 500;
    padding: 0.75rem 1.25rem;
    border-radius: 0.75rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
    transform-style: preserve-3d;
}

.nav-link:hover {
    color: #6d28d9;
    background: rgba(109, 40, 217, 0.1);
    transform: translateY(-2px) translateZ(10px);
    box-shadow: 0 4px 12px rgba(109, 40, 217, 0.15);
}

.nav-link i {
    transition: all 0.3s ease;
    transform-style: preserve-3d;
}

.nav-link:hover i {
    transform: scale(1.2) rotate(-5deg) translateZ(20px);
    color: #6d28d9;
}

.active-nav {
    color: #6d28d9;
    background: rgba(109, 40, 217, 0.15);
    box-shadow: 0 2px 8px rgba(109, 40, 217, 0.2);
}

/* Enhanced 3D Animations */
@keyframes glow {
    0% {
        text-shadow: 0 0 5px rgba(139, 92, 246, 0.2);
        transform: translateZ(0);
    }
    100% {
        text-shadow: 0 0 20px rgba(139, 92, 246, 0.4);
        transform: translateZ(10px);
    }
}

@keyframes shimmer {
    0% {
        background-position: -200% center;
        transform: translateZ(5px);
    }
    100% {
        background-position: 200% center;
        transform: translateZ(0);
    }
}

/* Floating animation with 3D effect */
@keyframes float {
    0%, 100% {
        transform: translateY(0) translateZ(0) rotateX(0);
    }
    50% {
        transform: translateY(-5px) translateZ(10px) rotateX(5deg);
    }
}

.logo-container {
    animation: float 3s ease-in-out infinite;
}

/* Enhanced hover effects with 3D */
.nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        120deg,
        transparent,
        rgba(139, 92, 246, 0.1),
        transparent
    );
    transition: 0.5s;
    transform: translateZ(-1px);
}

.nav-link:hover::before {
    left: 100%;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .logo-text {
        font-size: 2.5rem;
    }

    .nav-links-container {
        flex-direction: column;
        width: 100%;
        padding: 0.5rem;
    }

    .nav-link {
        width: 100%;
        margin: 0.25rem 0;
        justify-content: center;
    }
}

/* Pulse animation with 3D effect */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(109, 40, 217, 0.4);
        transform: translateZ(0);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(109, 40, 217, 0);
        transform: translateZ(5px);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(109, 40, 217, 0);
        transform: translateZ(0);
    }
}

.active-nav {
    animation: pulse 2s infinite;
}

/* Add subtle parallax effect */
.nav-links-container:hover .nav-link {
    transform: translateZ(5px);
}

.nav-links-container:hover .nav-link:hover {
    transform: translateZ(15px);
}
</style> 