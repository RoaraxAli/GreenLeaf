<footer class="footer mt-5 py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5 class="text-white mb-3">
                    <i class="fas fa-leaf me-2"></i><?php echo SITE_NAME; ?>
                </h5>
                <p class="mb-3">Your trusted partner in creating beautiful, thriving gardens. From indoor houseplants to outdoor landscapes, we help you grow your green paradise.</p>
                <div class="social-links">
                    <a href="#" class="me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-white mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/catalog.php">Plants</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/blogs.php">Garden Tips</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/about.php">About Us</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-white mb-3">Categories</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/catalog.php?category=Indoor">Indoor Plants</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/catalog.php?category=Outdoor">Outdoor Plants</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/catalog.php?category=Air-Purifying">Air Purifying</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/catalog.php?category=Flowering">Flowering</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/catalog.php?category=Seasonal">Seasonal</a></li>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-white mb-3">Account</h6>
                <ul class="list-unstyled">
                    <?php if (is_logged_in()): ?>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/profile.php">My Profile</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/my-garden.php">My Garden</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/orders.php">My Orders</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/reminders.php">Reminders</a></li>
                    <?php else: ?>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/auth/login.php">Login</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/auth/register.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-white mb-3">Support</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/help.php">Help Center</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/shipping.php">Shipping Info</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/returns.php">Returns</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/privacy.php">Privacy Policy</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>/terms.php">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        
        <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">
        
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0">Made with <i class="fas fa-heart text-danger"></i> for plant lovers</p>
            </div>
        </div>
    </div>
</footer>
