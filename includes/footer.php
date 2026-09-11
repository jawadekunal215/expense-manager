    </main><!-- end page-content -->
</div><!-- end main-wrapper -->

<!-- Add Transaction Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add Transaction</h3>
            <button class="modal-close" onclick="closeModal('addModal')"><i class="fas fa-times"></i></button>
        </div>
        <form action="../actions/add_transaction.php" method="POST" class="modal-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" id="txnType" onchange="loadCategories(this.value)" required>
                        <option value="expense">💸 Expense</option>
                        <option value="income">💰 Income</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (₹)</label>
                    <input type="number" name="amount" placeholder="0.00" step="0.01" min="0.01" required>
                </div>
            </div>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" placeholder="e.g. Monthly Groceries" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="categorySelect">
                        <option value="">-- Select Category --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method">
                    <option value="cash">💵 Cash</option>
                    <option value="card">💳 Card</option>
                    <option value="upi">📱 UPI</option>
                    <option value="bank_transfer">🏦 Bank Transfer</option>
                    <option value="other">🔄 Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description (optional)</label>
                <textarea name="description" placeholder="Add notes..."></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-check"></i> Save Transaction</button>
            </div>
        </form>
    </div>
</div>

<!-- FAB Button -->
<button class="fab-btn" onclick="openModal('addModal')" title="Add Transaction">
    <i class="fas fa-plus"></i>
</button>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script src="../assets/js/app.js"></script>
</body>
</html>
