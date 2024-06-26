        </main>
        <footer class="d-flex justify-content-between">
            <div>
                <div aria-label="著作年">
                    &copy;
                    <span itemprop="copyrightYear"><?= date('Y') ?></span>
                </div>
                <address itemprop="copyrightHolder" itemtype="OnlineBusiness">
                    <span itemprop="legalName" class="text-nowrap"><?= CONFIG['site.name'] ?></span>
                </address>
            </div>
            <div>
                <menu class="nav mt-0">
                    <?php if ($Session->user && $Session->user->role === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin/">管理後臺</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="privacy.php">隱私權政策</a></li>
                    <li class="nav-item"><a class="nav-link" href="terms.php">服務條款</a></li>
                </menu>
                <address itemprop="maintainer" itemtype="Organization"
                    class="fs-7 text-end text-muted"
                >
                    powered by
                    <span itemprop="name"><?= CONFIG['powered_by'] ?></span>
                </address>
            </div>
        </fotter>
    </div>
</body>
</html>
