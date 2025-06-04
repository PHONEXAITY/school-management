// Component templates and rendering functions

class ComponentRenderer {
    static renderNavigation() {
        return `
            <nav class="bg-white shadow-lg fixed w-full z-50">
                <div class="container mx-auto px-4">
                    <div class="flex justify-between items-center py-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center pulse-glow">
                                <i class="fas fa-graduation-cap text-white text-xl"></i>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">Sanfan Primary</h1>
                                <p class="text-sm text-gray-600">ໂຮງຮຽນປະຖົມສານຝັນ</p>
                            </div>
                        </div>

                        <div class="hidden md:flex space-x-6">
                            <a href="#" onclick="showPage('home')" class="nav-link text-gray-700 hover:text-blue-600 transition-colors">ໜ້າຫຼັກ</a>
                            <a href="#" onclick="showPage('register')" class="nav-link text-gray-700 hover:text-blue-600 transition-colors">ລົງທະບຽນ</a>
                            <a href="#" onclick="showPage('scores')" class="nav-link text-gray-700 hover:text-blue-600 transition-colors">ເບິ່ງຄະແນນ</a>
                            <a href="#" onclick="showPage('activities')" class="nav-link text-gray-700 hover:text-blue-600 transition-colors">ກິດຈະກຳ</a>
                            <a href="#" onclick="showPage('gallery')" class="nav-link text-gray-700 hover:text-blue-600 transition-colors">ຮູບພາບກິດຈະກຳ</a>
                            <a href="#" onclick="showPage('about')" class="nav-link text-gray-700 hover:text-blue-600 transition-colors">ກ່ຽວກັບພວກເຮົາ</a>
                        </div>

                        <button class="md:hidden" onclick="toggleMobileMenu()">
                            <i class="fas fa-bars text-2xl text-gray-700"></i>
                        </button>
                    </div>

                    <div id="mobileMenu" class="hidden md:hidden pb-4">
                        <a href="#" onclick="showPage('home')" class="block py-2 text-gray-700 hover:text-blue-600">ໜ້າຫຼັກ</a>
                        <a href="#" onclick="showPage('register')" class="block py-2 text-gray-700 hover:text-blue-600">ລົງທະບຽນ</a>
                        <a href="#" onclick="showPage('scores')" class="block py-2 text-gray-700 hover:text-blue-600">ເບິ່ງຄະແນນ</a>
                        <a href="#" onclick="showPage('activities')" class="block py-2 text-gray-700 hover:text-blue-600">ກິດຈະກຳ</a>
                        <a href="#" onclick="showPage('gallery')" class="block py-2 text-gray-700 hover:text-blue-600">ຮູບພາບກິດຈະກຳ</a>
                        <a href="#" onclick="showPage('about')" class="block py-2 text-gray-700 hover:text-blue-600">ກ່ຽວກັບພວກເຮົາ</a>
                    </div>
                </div>
            </nav>
        `;
    }

    static renderHeroSection() {
        return `
            <section class="gradient-bg min-h-screen flex items-center relative overflow-hidden">
                <div class="absolute inset-0 bg-black bg-opacity-20"></div>

                <!-- Particles -->
                <div class="absolute inset-0 overflow-hidden">
                    <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
                    <div class="particle" style="left: 20%; animation-delay: 1s;"></div>
                    <div class="particle" style="left: 30%; animation-delay: 2s;"></div>
                    <div class="particle" style="left: 40%; animation-delay: 3s;"></div>
                    <div class="particle" style="left: 50%; animation-delay: 4s;"></div>
                    <div class="particle" style="left: 60%; animation-delay: 5s;"></div>
                    <div class="particle" style="left: 70%; animation-delay: 2.5s;"></div>
                    <div class="particle" style="left: 80%; animation-delay: 1.5s;"></div>
                    <div class="particle" style="left: 90%; animation-delay: 3.5s;"></div>
                </div>

                <div class="container mx-auto px-4 relative z-10">
                    <div class="grid md:grid-cols-2 gap-12 items-center">
                        <div class="text-white fade-in-up">
                            <h1 class="text-5xl md:text-7xl font-bold mb-6">
                                ໂຮງຮຽນປະຖົມ
                                <span class="text-yellow-300 typing">ສານຝັນ</span>
                            </h1>
                            <p class="text-xl md:text-2xl mb-8 opacity-90">
                                ສ້າງອະນາຄົດທີ່ສົດໃສໃຫ້ລູກຫຼານຂອງທ່ານ ດ້ວຍການສຶກສາທີ່ມີຄຸນນະພາບ.
                            </p>
                            <div class="flex flex-col sm:flex-row gap-4 mb-8">
                                <button onclick="showPage('register')" class="bg-yellow-400 text-gray-900 px-8 py-4 rounded-full font-semibold text-lg hover:bg-yellow-300 transition-all hover-scale glow">
                                    <i class="fas fa-user-plus mr-2"></i>
                                    ລົງທະບຽນດຽວນີ້
                                </button>
                                <button onclick="showPage('scores')" class="glass-effect text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-white hover:bg-opacity-20 transition-all">
                                    <i class="fas fa-chart-line mr-2"></i>
                                    ເບິ່ງຜົນຄະແນນ
                                </button>
                            </div>

                            <!-- Achievement Badges -->
                            <div class="flex flex-wrap gap-4">
                                <div class="achievement-badge bg-white bg-opacity-20 px-4 py-2 rounded-full backdrop-blur-sm">
                                    <i class="fas fa-medal text-yellow-300 mr-2"></i>
                                    <span class="text-sm">ໂຮງຮຽນມາດຕະຖານ</span>
                                </div>
                                <div class="achievement-badge bg-white bg-opacity-20 px-4 py-2 rounded-full backdrop-blur-sm">
                                    <i class="fas fa-star text-yellow-300 mr-2"></i>
                                    <span class="text-sm">ລາງວັນເຍົາວະຊົນດີເດັ່ນ</span>
                                </div>
                                <div class="achievement-badge bg-white bg-opacity-20 px-4 py-2 rounded-full backdrop-blur-sm">
                                    <i class="fas fa-trophy text-yellow-300 mr-2"></i>
                                    <span class="text-sm">ໂຮງຮຽນສີຂຽວ</span>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <div class="floating-animation">
                                <div class="w-80 h-80 mx-auto bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                    <i class="fas fa-school text-8xl text-white opacity-80"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scroll Indicator -->
                <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 text-white scroll-indicator">
                    <i class="fas fa-chevron-down text-2xl"></i>
                </div>

                <!-- Floating Elements -->
                <div class="absolute top-20 left-10 w-20 h-20 bg-yellow-300 bg-opacity-30 rounded-full floating-animation"></div>
                <div class="absolute bottom-20 right-10 w-32 h-32 bg-blue-300 bg-opacity-30 rounded-full floating-animation" style="animation-delay: 1s;"></div>
                <div class="absolute top-1/2 left-20 w-16 h-16 bg-purple-300 bg-opacity-30 rounded-full floating-animation" style="animation-delay: 2s;"></div>
            </section>
        `;
    }

    static renderStatsSection() {
        return `
            <section class="py-20 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                <div class="container mx-auto px-4">
                    <div class="grid md:grid-cols-4 gap-8 text-center">
                        <div class="fade-in-up">
                            <div class="text-5xl font-bold mb-4 text-gradient" id="studentsCount">0</div>
                            <h3 class="text-xl font-semibold">ນັກຮຽນ</h3>
                            <p class="text-blue-200">ທັງໝົດ</p>
                        </div>
                        <div class="fade-in-up" style="animation-delay: 0.2s;">
                            <div class="text-5xl font-bold mb-4" id="teachersCount">0</div>
                            <h3 class="text-xl font-semibold">ຄູສອນ</h3>
                            <p class="text-blue-200">ມືອາຊີບ</p>
                        </div>
                        <div class="fade-in-up" style="animation-delay: 0.4s;">
                            <div class="text-5xl font-bold mb-4" id="awardsCount">0</div>
                            <h3 class="text-xl font-semibold">ລາງວັນ</h3>
                            <p class="text-blue-200">ທີ່ໄດ້ຮັບ</p>
                        </div>
                        <div class="fade-in-up" style="animation-delay: 0.6s;">
                            <div class="text-5xl font-bold mb-4" id="yearsCount">0</div>
                            <h3 class="text-xl font-semibold">ປີ</h3>
                            <p class="text-blue-200">ແຫ່ງການບໍລິການ</p>
                        </div>
                    </div>
                </div>
            </section>
        `;
    }

    static renderServicesSection() {
        return `
            <section class="py-20 bg-white">
                <div class="container mx-auto px-4">
                    <div class="text-center mb-16">
                        <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">ການບໍລິການຂອງພວກເຮົາ</h2>
                        <p class="text-xl text-gray-600">ລະບົບການຈັດການທີ່ເຮັດໃຫ້ການສຶກສາງ່າຍຂຶ້ນ</p>
                    </div>

                    <div class="grid md:grid-cols-3 gap-8">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-8 rounded-2xl card-hover cursor-pointer" onclick="showPage('register')">
                            <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center mb-6">
                                <i class="fas fa-user-graduate text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-4">ລົງທະບຽນນັກຮຽນ</h3>
                            <p class="text-gray-600 mb-6">ລະບົບລົງທະບຽນອອນລາຍທີ່ສະດວກ, ວ່ອງໄວ ແລະ ປອດໄພ</p>
                            <div class="flex items-center text-blue-600 font-semibold">
                                <span>ເລີ່ມລົງທະບຽນ</span>
                                <i class="fas fa-arrow-right ml-2"></i>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-8 rounded-2xl card-hover cursor-pointer" onclick="showPage('scores')">
                            <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mb-6">
                                <i class="fas fa-chart-bar text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-4">ກວດສອບຄະແນນ</h3>
                            <p class="text-gray-600 mb-6">ເບິ່ງຜົນການຮຽນຂອງລູກທ່ານໄດ້ທຸກບ່ອນ, ທຸກເວລາ ຜ່ານລະບົບອອນລາຍ</p>
                            <div class="flex items-center text-green-600 font-semibold">
                                <span>ເບິ່ງຄະແນນດຽວນີ້</span>
                                <i class="fas fa-arrow-right ml-2"></i>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-8 rounded-2xl card-hover">
                            <div class="w-16 h-16 bg-purple-500 rounded-full flex items-center justify-center mb-6">
                                <i class="fas fa-phone text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-4">ຕິດຕໍ່ສອບຖາມ</h3>
                            <p class="text-gray-600 mb-6">ທີມງານພ້ອມໃຫ້ຄຳປຶກສາ ແລະ ຊ່ວຍເຫຼືອຕະຫຼອດ 24 ຊົ່ວໂມງ</p>
                            <div class="flex items-center text-purple-600 font-semibold">
                                <span>ຕິດຕໍ່ພວກເຮົາ</span>
                                <i class="fas fa-arrow-right ml-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        `;
    }

    static renderFooter() {
        return `
            <footer class="bg-gray-800 text-white py-12">
                <div class="container mx-auto px-4">
                    <div class="grid md:grid-cols-4 gap-8">
                        <div>
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-graduation-cap text-white"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold">Sanfan Primary</h3>
                                    <p class="text-gray-400 text-sm">ໂຮງຮຽນປະຖົມສານຝັນ</p>
                                </div>
                            </div>
                            <p class="text-gray-400">ສ້າງອະນາຄົດທີ່ສົດໃສໃຫ້ລູກຫຼານຂອງທ່ານ ດ້ວຍການສຶກສາທີ່ມີຄຸນນະພາບ.</p>
                        </div>

                        <div>
                            <h4 class="text-lg font-semibold mb-4">ລິ້ງດ່ວນ</h4>
                            <ul class="space-y-2">
                                <li><a href="#" onclick="showPage('home')" class="text-gray-400 hover:text-white transition-colors">ໜ້າຫຼັກ</a></li>
                                <li><a href="#" onclick="showPage('register')" class="text-gray-400 hover:text-white transition-colors">ລົງທະບຽນ</a></li>
                                <li><a href="#" onclick="showPage('scores')" class="text-gray-400 hover:text-white transition-colors">ເບິ່ງຄະແນນ</a></li>
                                <li><a href="#" onclick="showPage('activities')" class="text-gray-400 hover:text-white transition-colors">ກິດຈະກຳ</a></li>
                                <li><a href="#" onclick="showPage('gallery')" class="text-gray-400 hover:text-white transition-colors">ຮູບພາບກິດຈະກຳ</a></li>
                                <li><a href="#" onclick="showPage('about')" class="text-gray-400 hover:text-white transition-colors">ກ່ຽວກັບພວກເຮົາ</a></li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="text-lg font-semibold mb-4">ຕິດຕໍ່ພວກເຮົາ</h4>
                            <ul class="space-y-2 text-gray-400">
                                <li><i class="fas fa-phone mr-2"></i> 02-123-4567</li>
                                <li><i class="fas fa-envelope mr-2"></i> info@sanfanprimary.ac.th</li>
                                <li><i class="fas fa-map-marker-alt mr-2"></i> ບ້ານພູເຫຼັກຈະເລີນ, ເມືອງຫຼວງພະບາງ ແຂວງຫຼວງພະບາງ</li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="text-lg font-semibold mb-4">ຕິດຕາມພວກເຮົາ</h4>
                            <div class="flex space-x-4">
                                <a href="#" class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center hover:bg-green-600 transition-colors">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <a href="#" class="w-10 h-10 bg-pink-500 rounded-full flex items-center justify-center hover:bg-pink-600 transition-colors">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                        <p class="text-gray-400">© 2025 Sanfan Primary School. ສະຫງວນລິຂະສິດທຸກປະການ</p>
                    </div>
                </div>
            </footer>
        `;
    }
}