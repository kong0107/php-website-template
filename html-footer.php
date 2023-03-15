        </main>
        <footer class="d-flex justify-content-between">
            <address itemprop="copyrightHolder" itemtype="OnlineBusiness">
                &copy;
                <span itemprop="copyrightYear"><?= date('Y') ?></span>
                <span itemprop="legalName" class="text-nowrap"><?= SITE_NAME ?></span>
            </address>
            <div>
                <menu class="nav mt-0">
                    <?php if($Session->user && $Session->user->role === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin/">管理後臺</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="privacy.php">隱私權政策</a></li>
                    <li class="nav-item"><a class="nav-link" href="terms.php">服務條款</a></li>
                </menu>
                <address itemprop="maintainer" itemtype="Organization"
                    class="fs-7 text-end text-muted"
                >
                    powered by
                    <span itemprop="name"><?= POWERED_BY ?></span>
                </address>
            </div>
        </fotter>
    </div>
</body>
</html>
