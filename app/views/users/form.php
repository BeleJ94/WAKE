<?php $source = $user ?? $old ?? []; ?>

<label>
    Nom complet
    <input class="<?= isset($errors['name']) ? 'is-invalid' : ''; ?>" type="text" name="name" required minlength="2" value="<?= e($source['name'] ?? ''); ?>">
    <?php if (isset($errors['name'])): ?><small class="field-error"><?= e($errors['name']); ?></small><?php endif; ?>
</label>

<label>
    Email
    <input class="<?= isset($errors['email']) ? 'is-invalid' : ''; ?>" type="email" name="email" required value="<?= e($source['email'] ?? ''); ?>">
    <?php if (isset($errors['email'])): ?><small class="field-error"><?= e($errors['email']); ?></small><?php endif; ?>
</label>

<label>
    Rôle
    <select class="<?= isset($errors['role_id']) ? 'is-invalid' : ''; ?>" name="role_id" required>
        <option value="">Sélectionner</option>
        <?php foreach ($roles as $role): ?>
            <option value="<?= (int) $role['id']; ?>" <?= (int) ($source['role_id'] ?? 0) === (int) $role['id'] ? 'selected' : ''; ?>>
                <?= e($role['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($errors['role_id'])): ?><small class="field-error"><?= e($errors['role_id']); ?></small><?php endif; ?>
</label>

<label>
    Statut
    <select name="status" required>
        <option value="active" <?= ($source['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Actif</option>
        <option value="inactive" <?= ($source['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactif</option>
    </select>
</label>

<label>
    Mot de passe <?= isset($user) ? '(laisser vide pour ne pas modifier)' : ''; ?>
    <input class="<?= isset($errors['password']) ? 'is-invalid' : ''; ?>" type="password" name="password" <?= isset($user) ? '' : 'required'; ?> minlength="8" autocomplete="new-password">
    <?php if (isset($errors['password'])): ?><small class="field-error"><?= e($errors['password']); ?></small><?php endif; ?>
</label>

