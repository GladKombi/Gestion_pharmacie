<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FOAMIS Sarl - Votre pharmacie de confiance. Produits pharmaceutiques de qualité, conseils santé personnalisés et service d'urgence 24h/24.">
    <meta name="keywords" content="pharmacie, santé, médicaments, conseils santé, urgence, FOAMIS">
    <title>FOAMIS Sarl - Votre Santé, Notre Priorité | Pharmacie de Référence</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Library for scroll animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #16a34a;
            --primary-dark: #15803d;
            --secondary-color: #047857;
            --light-green: #f0fff4;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            scroll-behavior: smooth;
            line-height: 1.6;
        }

        .hero-gradient {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.85) 0%, rgba(4, 120, 87, 0.85) 100%);
        }

        .bg-section-gradient {
            background: linear-gradient(120deg, #f0fff4 0%, #e6fffa 100%);
        }

        .bg-green-gradient {
            background: linear-gradient(135deg, #16a34a 0%, #059669 100%);
        }

        .btn-primary {
            background-color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(21, 128, 61, 0.3);
        }

        .stat-card {
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .product-card {
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .product-image {
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .team-member {
            transition: all 0.3s ease;
        }

        .team-member:hover {
            transform: translateY(-5px);
        }

        .article-card {
            transition: all 0.3s ease;
        }

        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .animate-fadeIn {
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
        }

        .nav-link {
            font-weight: 500;
            color: #374151;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color);
        }

        .dropdown-menu {
            background: white;
            border-radius: 0.375rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .btn-emergency {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            transition: all 0.3s ease;
        }

        .btn-emergency:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.4);
        }

        .mobile-menu {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        /* Back to top button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
            z-index: 1000;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .back-to-top.active {
            opacity: 1;
            visibility: visible;
        }
        
        .back-to-top:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
        }
        
        /* Testimonial slider */
        .testimonial-slider {
            overflow: hidden;
            position: relative;
        }
        
        .testimonial-track {
            display: flex;
            transition: transform 0.5s ease;
        }
        
        .testimonial-slide {
            min-width: 100%;
            padding: 0 15px;
        }
        
        .testimonial-dots {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .testimonial-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ddd;
            margin: 0 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .testimonial-dot.active {
            background: var(--primary-color);
        }
        
        /* Focus styles for better accessibility */
        a:focus, button:focus, input:focus, textarea:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
        
        /* Skip to main content link */
        .skip-link {
            position: absolute;
            top: -40px;
            left: 10px;
            background: var(--primary-color);
            color: white;
            padding: 8px;
            z-index: 1001;
            transition: top 0.3s;
        }
        
        .skip-link:focus {
            top: 10px;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800">
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="skip-link">Aller au contenu principal</a>

    <!-- Back to top button -->
    <div class="back-to-top" id="backToTop">
        <i class="fas fa-chevron-up"></i>
    </div>

    <!-- NAVBAR -->
    <header class="fixed w-full top-0 z-50 navbar" id="navbar">
        <!-- Navigation -->
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="#" class="flex items-center space-x-2">
                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-green-500 to-green-700 flex items-center justify-center">
                    <span class="text-white font-bold text-sm">FS</span>
                </div>
                <h1 class="text-lg font-semibold text-green-700">FOAMIS Sarl</h1>
            </a>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-button" class="md:hidden text-green-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex space-x-6 items-center text-sm">
                <a href="#accueil" class="nav-link font-medium hover:text-green-600 transition-colors">Accueil</a>
                <a href="#propos" class="nav-link font-medium hover:text-green-600 transition-colors">À propos</a>
                <a href="#services" class="nav-link font-medium hover:text-green-600 transition-colors">Nos Services</a>
                <a href="#produits" class="nav-link font-medium hover:text-green-600 transition-colors">Nos Produits</a>
                <a href="#equipe" class="nav-link font-medium hover:text-green-600 transition-colors">Notre Équipe</a>
                <a href="#contact" class="nav-link font-medium hover:text-green-600 transition-colors">Contact</a>

                <!-- Dropdown Espace Pro -->
                <div class="relative dropdown">  
                    <button class="nav-link font-medium hover:text-green-600 transition-colors flex items-center">
                        Espace Pro <i class="fas fa-chevron-down ml-1 text-xs"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg py-1 dropdown-menu hidden z-10 border border-gray-100">
                        <a href="admin/login.php?coordonateur" class="block px-3 py-2 text-xs text-gray-700 hover:bg-green-50">Coordonateur</a>
                        <a href="admin/login.php?Admin" class="block px-3 py-2 text-xs text-gray-700 hover:bg-green-50">Administrateur</a>
                        <a href="admin/login.php?Comunity" class="block px-3 py-2 text-xs text-gray-700 hover:bg-green-50">Comunity M.</a>
                    </div>
                </div>

                <!-- Bouton Urgence -->
                <a href="tel:+33123456790" class="btn-emergency text-white font-semibold py-2 px-4 rounded-full text-sm">
                    <i class="fas fa-phone-alt mr-1"></i> Urgence
                </a>
            </nav>
        </div>

        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="md:hidden bg-white py-2 px-4 shadow-lg hidden border-t">
            <a href="#accueil" class="block py-2 text-sm text-gray-700 font-medium hover:text-green-600">Accueil</a>
            <a href="#propos" class="block py-2 text-sm text-gray-700 font-medium hover:text-green-600">À propos</a>
            <a href="#services" class="block py-2 text-sm text-gray-700 font-medium hover:text-green-600">Nos Services</a>
            <a href="#produits" class="block py-2 text-sm text-gray-700 font-medium hover:text-green-600">Nos Produits</a>
            <a href="#equipe" class="block py-2 text-sm text-gray-700 font-medium hover:text-green-600">Notre Équipe</a>
            <a href="#contact" class="block py-2 text-sm text-gray-700 font-medium hover:text-green-600">Contact</a>
            <a href="admin/login.php?coordonateur" class="block py-2 text-sm text-gray-700 font-medium hover:text-green-600">Espace Coordonateur</a>
            <a href="admin/login.php?Admin" class="block py-2 text-sm text-gray-700 font-medium hover:text-green-600">Espace Administrateur</a>
            <a href="admin/login.php?Comunity" class="block py-2 text-sm text-gray-700 font-medium hover:text-green-600">Espace Community M.</a>
            <a href="tel:+33123456790" class="block mt-2 py-2 text-center btn-emergency text-white font-semibold rounded-full text-sm">
                <i class="fas fa-phone-alt mr-1"></i> Urgence
            </a>
        </div>
    </header>

    <main id="main-content">
        <section id="accueil" class="pt-24 md:pt-32 pb-16 md:pb-24 bg-cover bg-center relative" style="background-image: url('https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');">
            <div class="hero-gradient absolute inset-0"></div>
            <div class="container mx-auto px-6 text-center relative z-10">
                <h1 class="text-4xl md:text-6xl font-extrabold mb-6 text-white animate-fadeIn" data-aos="fade-up">
                    Votre Santé, <span class="text-green-300">Notre Priorité</span>
                </h1>
                <p class="text-xl md:text-2xl mb-10 max-w-3xl mx-auto text-green-100 animate-fadeIn" data-aos="fade-up" data-aos-delay="200">
                    Pharmacie de référence pour des conseils santé personnalisés et des produits de qualité.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4 animate-fadeIn" data-aos="fade-up" data-aos-delay="400">
                    <a href="#services" class="btn-primary text-white font-semibold py-3 px-8 rounded-full inline-flex items-center justify-center">
                        <i class="fas fa-heartbeat mr-2" aria-hidden="true"></i> Nos Services
                    </a>
                    <a href="#contact" class="bg-white text-green-700 font-semibold py-3 px-8 rounded-full inline-flex items-center justify-center">
                        <i class="fas fa-map-marker-alt mr-2" aria-hidden="true"></i> Nous trouver
                    </a>
                </div>
            </div>
        </section>

        <section class="py-8 bg-white shadow-sm">
            <div class="container mx-auto px-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="stat-card bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-6 text-center border border-green-100" data-aos="fade-up" data-aos-delay="100">
                        <div class="text-3xl font-bold text-green-600 mb-2">24/7</div>
                        <div class="text-gray-600">Service d'urgence</div>
                    </div>
                    <div class="stat-card bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-6 text-center border border-green-100" data-aos="fade-up" data-aos-delay="200">
                        <div class="text-3xl font-bold text-green-600 mb-2">5000+</div>
                        <div class="text-gray-600">Produits disponibles</div>
                    </div>
                    <div class="stat-card bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-6 text-center border border-green-100" data-aos="fade-up" data-aos-delay="300">
                        <div class="text-3xl font-bold text-green-600 mb-2">15+</div>
                        <div class="text-gray-600">Années d'expérience</div>
                    </div>
                    <div class="stat-card bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-6 text-center border border-green-100" data-aos="fade-up" data-aos-delay="400">
                        <div class="text-3xl font-bold text-green-600 mb-2">100%</div>
                        <div class="text-gray-600">Satisfaction client</div>
                    </div>
                </div>
            </div>
        </section>

        <section id="propos" class="py-16 md:py-24 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-green-700 mb-4" data-aos="fade-up">Notre Mission</h2>
                    <div class="h-1 w-20 bg-green-500 mx-auto" data-aos="fade-up" data-aos-delay="200"></div>
                    <p class="text-gray-600 max-w-3xl mx-auto mt-6" data-aos="fade-up" data-aos-delay="300">
                        FOAMIS Sarl s'engage à fournir des produits pharmaceutiques de qualité et des conseils santé personnalisés pour le bien-être de notre communauté.
                    </p>
                </div>

                <div class="flex flex-col md:flex-row items-center gap-12">
                    <div class="md:w-1/2" data-aos="fade-right">
                        <div class="rounded-xl overflow-hidden shadow-xl">
                            <img src="https://images.unsplash.com/photo-1551076805-e1869033e561?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2032&q=80" alt="Pharmacie FOAMIS" class="w-full h-auto">
                        </div>
                    </div>
                    <div class="md:w-1/2" data-aos="fade-left">
                        <h3 class="text-2xl font-bold text-green-800 mb-4">Notre Engagement</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            Depuis plus de 15 ans, FOAMIS Sarl est le partenaire santé de confiance pour des milliers de patients. Notre équipe de pharmaciens diplômés vous accompagne dans tous vos besoins de santé.
                        </p>

                        <h3 class="text-2xl font-bold text-green-800 mb-4">Nos Valeurs</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-green-600 mb-2"><i class="fas fa-user-md text-xl" aria-hidden="true"></i></div>
                                <h4 class="font-semibold text-green-700 mb-1">Expertise</h4>
                                <p class="text-sm text-gray-600">Conseils professionnels personnalisés.</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-green-600 mb-2"><i class="fas fa-shield-alt text-xl" aria-hidden="true"></i></div>
                                <h4 class="font-semibold text-green-700 mb-1">Qualité</h4>
                                <p class="text-sm text-gray-600">Produits certifiés et contrôlés.</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-green-600 mb-2"><i class="fas fa-heart text-xl" aria-hidden="true"></i></div>
                                <h4 class="font-semibold text-green-700 mb-1">Bien-être</h4>
                                <p class="text-sm text-gray-600">Votre santé avant tout.</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-green-600 mb-2"><i class="fas fa-clock text-xl" aria-hidden="true"></i></div>
                                <h4 class="font-semibold text-green-700 mb-1">Disponibilité</h4>
                                <p class="text-sm text-gray-600">Service continu 24h/24.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Nouvelle section Témoignages -->
        <section class="py-16 md:py-24 bg-section-gradient">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-green-700 mb-4" data-aos="fade-up">Ce que disent nos clients</h2>
                    <div class="h-1 w-20 bg-green-500 mx-auto" data-aos="fade-up" data-aos-delay="200"></div>
                </div>
                
                <div class="testimonial-slider max-w-4xl mx-auto" data-aos="fade-up">
                    <div class="testimonial-track" id="testimonialTrack">
                        <div class="testimonial-slide">
                            <div class="bg-white rounded-xl shadow-md p-8 text-center">
                                <div class="text-yellow-400 text-2xl mb-4">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                                <p class="text-gray-600 italic mb-6">
                                    "Une pharmacie exceptionnelle avec un personnel très compétent et attentionné. Ils ont toujours le bon conseil et prennent le temps d'écouter. Je recommande vivement !"
                                </p>
                                <div class="font-semibold text-green-700">Marie D.</div>
                                <div class="text-gray-500 text-sm">Patiente depuis 5 ans</div>
                            </div>
                        </div>
                        <div class="testimonial-slide">
                            <div class="bg-white rounded-xl shadow-md p-8 text-center">
                                <div class="text-yellow-400 text-2xl mb-4">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                                <p class="text-gray-600 italic mb-6">
                                    "Service d'urgence impeccable ! J'ai eu besoin d'un médicament en pleine nuit et ils ont été disponibles et efficaces. Un vrai service public !"
                                </p>
                                <div class="font-semibold text-green-700">Pierre L.</div>
                                <div class="text-gray-500 text-sm">Client régulier</div>
                            </div>
                        </div>
                        <div class="testimonial-slide">
                            <div class="bg-white rounded-xl shadow-md p-8 text-center">
                                <div class="text-yellow-400 text-2xl mb-4">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                                <p class="text-gray-600 italic mb-6">
                                    "Les conseils du pharmacien pour les produits de parapharmacie sont toujours pertinents. J'ai trouvé exactement ce qu'il me fallait pour mes problèmes de peau."
                                </p>
                                <div class="font-semibold text-green-700">Sophie M.</div>
                                <div class="text-gray-500 text-sm">Nouvelle cliente</div>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-dots" id="testimonialDots">
                        <!-- Dots will be generated by JavaScript -->
                    </div>
                </div>
            </div>
        </section>

        <section id="services" class="py-16 md:py-24 bg-section-gradient">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-green-700 mb-4" data-aos="fade-up">Nos Services</h2>
                    <div class="h-1 w-20 bg-green-500 mx-auto" data-aos="fade-up" data-aos-delay="200"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="product-card bg-white rounded-xl shadow-md overflow-hidden" data-aos="fade-up" data-aos-delay="100">
                        <div class="overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1585435557343-3b092031d5ad?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Conseil pharmaceutique" class="w-full h-56 object-cover product-image" loading="lazy">
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-green-800 mb-2">Conseil Pharmaceutique</h3>
                            <p class="text-gray-600 text-sm mb-4">Nos pharmaciens vous accompagnent dans le choix de vos médicaments et produits de santé avec des conseils personnalisés.</p>
                            <a href="#contact" class="text-green-600 font-semibold text-sm inline-flex items-center hover:text-green-700">
                                Prendre rendez-vous <i class="fas fa-arrow-right ml-2 text-xs" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>

                    <div class="product-card bg-white rounded-xl shadow-md overflow-hidden" data-aos="fade-up" data-aos-delay="200">
                        <div class="overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Préparation de médicaments" class="w-full h-56 object-cover product-image" loading="lazy">
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-green-800 mb-2">Préparation de Médicaments</h3>
                            <p class="text-gray-600 text-sm mb-4">Service spécialisé dans la préparation de traitements personnalisés selon les prescriptions médicales.</p>
                            <a href="#contact" class="text-green-600 font-semibold text-sm inline-flex items-center hover:text-green-700">
                                En savoir plus <i class="fas fa-arrow-right ml-2 text-xs" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>

                    <div class="product-card bg-white rounded-xl shadow-md overflow-hidden" data-aos="fade-up" data-aos-delay="300">
                        <div class="overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2030&q=80" alt="Service d'urgence" class="w-full h-56 object-cover product-image" loading="lazy">
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-green-800 mb-2">Service d'Urgence</h3>
                            <p class="text-gray-600 text-sm mb-4">Disponible 24h/24 et 7j/7 pour répondre à vos besoins urgents en médicaments et produits de santé.</p>
                            <a href="tel:+33123456790" class="text-green-600 font-semibold text-sm inline-flex items-center hover:text-green-700">
                                Contact urgent <i class="fas fa-arrow-right ml-2 text-xs" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="produits" class="py-16 md:py-24 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-green-700 mb-4" data-aos="fade-up">Nos Domaines d'Expertise</h2>
                    <div class="h-1 w-20 bg-green-500 mx-auto" data-aos="fade-up" data-aos-delay="200"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-green-50 rounded-lg p-6 text-center border border-green-100 hover:shadow-md transition-shadow" data-aos="fade-up" data-aos-delay="100">
                        <div class="text-green-600 text-3xl mb-4">
                            <i class="fas fa-capsules" aria-hidden="true"></i>
                        </div>
                        <h3 class="font-bold text-green-800 mb-2">Médicaments</h3>
                        <p class="text-gray-600 text-sm">Large gamme de médicaments prescrits et en vente libre</p>
                    </div>

                    <div class="bg-green-50 rounded-lg p-6 text-center border border-green-100 hover:shadow-md transition-shadow" data-aos="fade-up" data-aos-delay="200">
                        <div class="text-green-600 text-3xl mb-4">
                            <i class="fas fa-heart" aria-hidden="true"></i>
                        </div>
                        <h3 class="font-bold text-green-800 mb-2">Parapharmacie</h3>
                        <p class="text-gray-600 text-sm">Produits de beauté, d'hygiène et de bien-être</p>
                    </div>

                    <div class="bg-green-50 rounded-lg p-6 text-center border border-green-100 hover:shadow-md transition-shadow" data-aos="fade-up" data-aos-delay="300">
                        <div class="text-green-600 text-3xl mb-4">
                            <i class="fas fa-baby" aria-hidden="true"></i>
                        </div>
                        <h3 class="font-bold text-green-800 mb-2">Puériculture</h3>
                        <p class="text-gray-600 text-sm">Produits spécialisés pour bébés et jeunes enfants</p>
                    </div>

                    <div class="bg-green-50 rounded-lg p-6 text-center border border-green-100 hover:shadow-md transition-shadow" data-aos="fade-up" data-aos-delay="400">
                        <div class="text-green-600 text-3xl mb-4">
                            <i class="fas fa-stethoscope" aria-hidden="true"></i>
                        </div>
                        <h3 class="font-bold text-green-800 mb-2">Matériel Médical</h3>
                        <p class="text-gray-600 text-sm">Équipements et dispositifs médicaux</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="equipe" class="py-16 md:py-24 bg-section-gradient">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-green-700 mb-4" data-aos="fade-up">Notre Équipe</h2>
                    <div class="h-1 w-20 bg-green-500 mx-auto" data-aos="fade-up" data-aos-delay="200"></div>
                    <p class="text-gray-600 max-w-3xl mx-auto mt-6" data-aos="fade-up" data-aos-delay="300">
                        Une équipe de pharmaciens diplômés et dévoués à votre service
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="team-member bg-gradient-to-b from-green-50 to-white rounded-xl p-6 text-center shadow-md" data-aos="fade-up" data-aos-delay="100">
                        <div class="h-32 w-32 rounded-full mx-auto mb-4 overflow-hidden border-4 border-white shadow-md">
                            <img src="https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Pharmacien principal" class="h-full w-full object-cover" loading="lazy">
                        </div>
                        <h3 class="text-xl font-bold text-green-800 mb-1">Dr. Marie K.</h3>
                        <p class="text-green-600 text-sm font-medium mb-3">Pharmacienne Titulaire</p>
                        <p class="text-gray-600 text-sm">Docteur en pharmacie avec 15 ans d'expérience, spécialisée en pharmacie clinique.</p>
                    </div>
                    
                    <div class="team-member bg-gradient-to-b from-green-50 to-white rounded-xl p-6 text-center shadow-md" data-aos="fade-up" data-aos-delay="200">
                        <div class="h-32 w-32 rounded-full mx-auto mb-4 overflow-hidden border-4 border-white shadow-md">
                            <img src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Pharmacien adjoint" class="h-full w-full object-cover" loading="lazy">
                        </div>
                        <h3 class="text-xl font-bold text-green-800 mb-1">Dr. Jean L.</h3>
                        <p class="text-green-600 text-sm font-medium mb-3">Pharmacien Adjoint</p>
                        <p class="text-gray-600 text-sm">Spécialiste en galénique et préparation de médicaments personnalisés.</p>
                    </div>
                    
                    <div class="team-member bg-gradient-to-b from-green-50 to-white rounded-xl p-6 text-center shadow-md" data-aos="fade-up" data-aos-delay="300">
                        <div class="h-32 w-32 rounded-full mx-auto mb-4 overflow-hidden border-4 border-white shadow-md">
                            <img src="https://images.unsplash.com/photo-1594824947933-d0501ba2fe65?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Préparateur" class="h-full w-full object-cover" loading="lazy">
                        </div>
                        <h3 class="text-xl font-bold text-green-800 mb-1">Sophie M.</h3>
                        <p class="text-green-600 text-sm font-medium mb-3">Préparatrice en Pharmacie</p>
                        <p class="text-gray-600 text-sm">Expérimentée dans la gestion des stocks et la préparation des commandes.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="contact" class="py-16 md:py-24 bg-white">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-green-700 mb-4" data-aos="fade-up">Contactez-nous</h2>
                    <div class="h-1 w-20 bg-green-500 mx-auto" data-aos="fade-up" data-aos-delay="200"></div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <div data-aos="fade-right">
                        <h3 class="text-2xl font-bold text-green-800 mb-6">Nos Coordonnées</h3>
                        
                        <div class="space-y-6">
                            <div class="flex items-start">
                                <div class="bg-green-100 p-3 rounded-full mr-4">
                                    <i class="fas fa-map-marker-alt text-green-600" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">Adresse</h4>
                                    <p class="text-gray-600">123 Avenue de la Santé<br>75000 Paris, France</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="bg-green-100 p-3 rounded-full mr-4">
                                    <i class="fas fa-phone-alt text-green-600" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">Téléphone</h4>
                                    <p class="text-gray-600">+33 1 23 45 67 89<br>Urgence: +33 1 23 45 67 90</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="bg-green-100 p-3 rounded-full mr-4">
                                    <i class="fas fa-envelope text-green-600" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">Email</h4>
                                    <p class="text-gray-600">contact@foamis-pharma.com<br>urgence@foamis-pharma.com</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="bg-green-100 p-3 rounded-full mr-4">
                                    <i class="fas fa-clock text-green-600" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">Horaires</h4>
                                    <p class="text-gray-600">
                                        Lun-Sam: 8h00-20h00<br>
                                        Dim: 9h00-13h00<br>
                                        <strong>Service d'urgence: 24h/24</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Carte Google Maps intégrée -->
                        <div class="mt-8 rounded-xl overflow-hidden shadow-md" data-aos="fade-up" data-aos-delay="300">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.991440608176!2d2.292292615674525!3d48.85837360866204!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66e2964e34e2d%3A0x8ddca9ee380ef7e0!2sTour%20Eiffel!5e0!3m2!1sfr!2sfr!4v1647002349944!5m2!1sfr!2sfr" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Localisation de la pharmacie FOAMIS Sarl"></iframe>
                        </div>
                    </div>

                    <div class="bg-green-50 rounded-xl p-8" data-aos="fade-left">
                        <h3 class="text-2xl font-bold text-green-800 mb-6">Envoyez-nous un message</h3>
                        <form class="space-y-4" id="contactForm">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="lastname" class="block text-gray-700 mb-2">Nom</label>
                                    <input type="text" id="lastname" name="lastname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                                </div>
                                <div>
                                    <label for="firstname" class="block text-gray-700 mb-2">Prénom</label>
                                    <input type="text" id="firstname" name="firstname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                                </div>
                            </div>
                            <div>
                                <label for="email" class="block text-gray-700 mb-2">Email</label>
                                <input type="email" id="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                            </div>
                            <div>
                                <label for="subject" class="block text-gray-700 mb-2">Sujet</label>
                                <input type="text" id="subject" name="subject" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required>
                            </div>
                            <div>
                                <label for="message" class="block text-gray-700 mb-2">Message</label>
                                <textarea id="message" name="message" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" required></textarea>
                            </div>
                            <button type="submit" class="btn-primary text-white font-semibold py-3 px-6 rounded-lg w-full">
                                <i class="fas fa-paper-plane mr-2" aria-hidden="true"></i> Envoyer le message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gray-800 text-white pt-16 pb-8">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                <div>
                    <h3 class="text-xl font-bold mb-6">FOAMIS Sarl</h3>
                    <p class="text-gray-400 mb-6">
                        Votre partenaire santé de confiance depuis plus de 15 ans. Engagement, qualité et proximité.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300" aria-label="Suivez-nous sur Facebook">
                            <i class="fab fa-facebook-f text-lg" aria-hidden="true"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300" aria-label="Suivez-nous sur Twitter">
                            <i class="fab fa-twitter text-lg" aria-hidden="true"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300" aria-label="Suivez-nous sur Instagram">
                            <i class="fab fa-instagram text-lg" aria-hidden="true"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300" aria-label="Suivez-nous sur LinkedIn">
                            <i class="fab fa-linkedin-in text-lg" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-6">Liens rapides</h3>
                    <ul class="space-y-3">
                        <li><a href="#accueil" class="text-gray-400 hover:text-white transition duration-300">Accueil</a></li>
                        <li><a href="#propos" class="text-gray-400 hover:text-white transition duration-300">À Propos</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-white transition duration-300">Nos Services</a></li>
                        <li><a href="#produits" class="text-gray-400 hover:text-white transition duration-300">Nos Produits</a></li>
                        <li><a href="#equipe" class="text-gray-400 hover:text-white transition duration-300">Notre Équipe</a></li>
                        <li><a href="#contact" class="text-gray-400 hover:text-white transition duration-300">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-6">Services</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Conseil Pharmaceutique</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Préparation de Médicaments</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Service d'Urgence</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Livraison à Domicile</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-6">Informations légales</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Mentions légales</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Politique de confidentialité</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Conditions générales</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Plan du site</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-12 pt-8 text-center text-gray-400">
                <p>&copy; 2024 FOAMIS Sarl. Tous droits réservés.</p>
                <p class="text-sm mt-2">Pharmacie agréée - N° d'agrément: PH123456789</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS (Animate On Scroll)
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });

        // Navigation scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            const backToTop = document.getElementById('backToTop');
            
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            
            // Show/hide back to top button
            if (window.scrollY > 300) {
                backToTop.classList.add('active');
            } else {
                backToTop.classList.remove('active');
            }
        });

        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        // Close mobile menu when clicking on a link
        document.querySelectorAll('#mobile-menu a').forEach(link => {
            link.addEventListener('click', function() {
                document.getElementById('mobile-menu').classList.add('hidden');
            });
        });

        // Back to top functionality
        document.getElementById('backToTop').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Dropdown functionality
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            const button = dropdown.querySelector('button');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                menu.classList.add('hidden');
            });
        });

        // Testimonial slider
        let currentSlide = 0;
        const slides = document.querySelectorAll('.testimonial-slide');
        const track = document.getElementById('testimonialTrack');
        const dotsContainer = document.getElementById('testimonialDots');
        
        // Create dots
        slides.forEach((_, index) => {
            const dot = document.createElement('div');
            dot.classList.add('testimonial-dot');
            if (index === 0) dot.classList.add('active');
            dot.addEventListener('click', () => goToSlide(index));
            dotsContainer.appendChild(dot);
        });
        
        function goToSlide(slideIndex) {
            currentSlide = slideIndex;
            track.style.transform = `translateX(-${currentSlide * 100}%)`;
            
            // Update dots
            document.querySelectorAll('.testimonial-dot').forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }
        
        // Auto-advance slides
        setInterval(() => {
            currentSlide = (currentSlide + 1) % slides.length;
            goToSlide(currentSlide);
        }, 5000);

        // Form submission
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simple form validation
            const formData = new FormData(this);
            let isValid = true;
            
            for (let [key, value] of formData) {
                if (!value.trim()) {
                    isValid = false;
                    break;
                }
            }
            
            if (isValid) {
                // In a real application, you would send the data to a server here
                alert('Merci pour votre message ! Nous vous répondrons dans les plus brefs délais.');
                this.reset();
            } else {
                alert('Veuillez remplir tous les champs du formulaire.');
            }
        });
    </script>
</body>
</html>