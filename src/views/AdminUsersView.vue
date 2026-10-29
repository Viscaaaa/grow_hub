<template>
  <div class="page admin-users-page">
    <!-- Page Header with comfortable margin -->
    <div class="page-header">
      <div>
        <div class="header-badge mb-1">
          <ShieldCheck :size="14" />
          <span>Administration</span>
        </div>
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">
          Manage employee accounts, assign permissions, and onboard new retail team members.
        </p>
      </div>

      <button
        type="button"
        class="btn btn-primary btn-add-user"
        @click="openAddModal"
      >
        <UserPlus :size="18" />
        <span>Add New User</span>
      </button>
    </div>

    <!-- Stats Overview Cards with comfortable spacing -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon-wrapper bg-green-soft">
          <Users :size="20" class="text-primary" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Total Registered</span>
          <span class="stat-val">{{ stats.total_users || 0 }}</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper bg-blue-soft">
          <UserCheck :size="20" class="text-blue" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Employees</span>
          <span class="stat-val">{{ stats.total_employees || 0 }}</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper bg-amber-soft">
          <ShieldCheck :size="20" class="text-amber" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Reviewers</span>
          <span class="stat-val">{{ stats.total_reviewers || 0 }}</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon-wrapper bg-purple-soft">
          <Award :size="20" class="text-purple" />
        </div>
        <div class="stat-meta">
          <span class="stat-label">Administrators</span>
          <span class="stat-val">{{ stats.total_admins || 0 }}</span>
        </div>
      </div>
    </div>

    <!-- Filter & Search Bar with generous padding -->
    <div class="card filter-bar">
      <div class="filter-row">
        <!-- Search input -->
        <div class="search-box">
          <Search :size="17" class="search-icon" />
          <input
            v-model="searchQuery"
            type="text"
            class="search-input"
            placeholder="Search by name, NIP, department, or position..."
            @input="debouncedFetch"
          />
          <button
            v-if="searchQuery"
            type="button"
            class="clear-search-btn"
            @click="searchQuery = ''; fetchUsers(1)"
          >
            <X :size="14" />
          </button>
        </div>

        <!-- Role Filter -->
        <div class="filter-group">
          <select v-model="selectedRole" class="filter-select" @change="fetchUsers(1)">
            <option value="">All Roles</option>
            <option value="employee">Employees</option>
            <option value="reviewer">Reviewers</option>
            <option value="admin">Administrators</option>
          </select>
        </div>

        <!-- Department Filter -->
        <div class="filter-group">
          <select v-model="selectedDept" class="filter-select" @change="fetchUsers(1)">
            <option value="">All Departments</option>
            <option v-for="dept in departments" :key="dept" :value="dept">
              {{ dept }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- User List Table Card with clean spacing -->
    <div class="card card-table">
      <!-- Table loading state -->
      <div v-if="loading" class="table-loading-container">
        <div class="spinner"></div>
      </div>

      <!-- Empty state -->
      <div v-else-if="users.length === 0" class="empty-state py-5 text-center">
        <div class="empty-icon-circle mb-2">
          <Users :size="28" class="muted" />
        </div>
        <h3 class="bold mb-1">No users found</h3>
        <p class="muted small mb-3">No employee records match the given criteria.</p>
        <button type="button" class="btn btn-outline btn-sm" @click="resetFilters">
          Reset Filters
        </button>
      </div>

      <!-- Table -->
      <div v-else class="table-responsive">
        <table class="user-table">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Department & Position</th>
              <th>Role</th>
              <th>Level & Points</th>
              <th>Daily Streak</th>
              <th>Status</th>
              <th>Joined Date</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id" class="user-row">
              <!-- Employee Info -->
              <td>
                <div class="user-cell">
                  <div class="user-avatar-circle">
                    <img
                      v-if="user.avatar_url"
                      :src="user.avatar_url"
                      :alt="user.name"
                      class="user-avatar-img"
                    />
                    <span v-else class="avatar-initials">
                      {{ getInitials(user.name) }}
                    </span>
                  </div>
                  <div class="user-info-text">
                    <span class="user-full-name bold">{{ user.name }}</span>
                    <span class="user-nip-badge">NIP: {{ user.nip }}</span>
                  </div>
                </div>
              </td>

              <!-- Department & Position -->
              <td>
                <div class="dept-cell">
                  <span class="dept-name bold">{{ user.department || 'Retail Store' }}</span>
                  <span class="position-title small muted">{{ user.position || 'Team Member' }}</span>
                </div>
              </td>

              <!-- Role -->
              <td>
                <span
                  class="role-badge"
                  :class="{
                    'role-admin': user.role === 'admin',
                    'role-reviewer': user.role === 'reviewer',
                    'role-employee': user.role === 'employee' || !user.role
                  }"
                >
                  <ShieldCheck v-if="user.role === 'reviewer'" :size="12" />
                  <Award v-else-if="user.role === 'admin'" :size="12" />
                  <User v-else :size="12" />
                  <span>{{ formatRole(user.role) }}</span>
                </span>
              </td>

              <!-- Gamification: Level & Points -->
              <td>
                <div class="points-cell">
                  <span class="level-tag">Lv.{{ user.level_info?.level || 1 }} {{ user.level_info?.name || 'Seedling' }}</span>
                  <span class="points-val small text-primary bold">
                    <Sparkles :size="12" />
                    {{ formatNumber(user.points) }} Pts
                  </span>
                </div>
              </td>

              <!-- Streak -->
              <td>
                <div class="streak-badge" :class="{ 'streak-active': user.current_streak > 0 }">
                  <Flame :size="14" />
                  <span>{{ user.current_streak || 0 }} Days</span>
                </div>
              </td>

              <!-- Status -->
              <td>
                <div class="status-stack">
                  <span
                    class="status-indicator"
                    :class="user.is_active !== false ? 'status-active' : 'status-inactive'"
                  >
                    <span class="status-dot"></span>
                    <span>{{ user.is_active !== false ? 'Active' : 'Inactive' }}</span>
                  </span>
                  <span
                    v-if="user.must_change_password"
                    class="badge-pwd-pending"
                    title="User wajib mengganti password saat login berikutnya"
                  >
                    <KeyRound :size="11" />
                    <span>Must Change Password</span>
                  </span>
                </div>
              </td>

              <!-- Joined Date -->
              <td class="small muted">
                {{ formatDate(user.created_at) }}
              </td>

              <!-- Actions (Edit, Reset & Delete) -->
              <td class="text-right">
                <div class="actions-cell">
                  <button
                    type="button"
                    class="action-btn key-btn"
                    title="Reset Password to Default (NIP)"
                    aria-label="Reset Password"
                    @click="openResetModal(user)"
                  >
                    <KeyRound :size="14" />
                  </button>
                  <button
                    type="button"
                    class="action-btn edit-btn"
                    title="Edit Employee"
                    aria-label="Edit Employee"
                    @click="openEditModal(user)"
                  >
                    <Pencil :size="14" />
                  </button>
                  <button
                    type="button"
                    class="action-btn delete-btn"
                    :disabled="user.id === authStore.user?.id"
                    :title="user.id === authStore.user?.id ? 'Cannot delete your own logged-in account' : 'Delete Employee'"
                    aria-label="Delete Employee"
                    @click="openDeleteModal(user)"
                  >
                    <Trash2 :size="14" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer with generous padding matching card borders -->
      <div v-if="pagination.total > 0" class="card-pagination-footer">
        <div class="pagination-info">
          Showing <span class="bold">{{ pagination.from || (users.length ? 1 : 0) }}</span> – <span class="bold">{{ pagination.to || users.length }}</span> of <span class="bold">{{ pagination.total }}</span> users
        </div>

        <div v-if="pagination.last_page > 1" class="pagination-actions">
          <button
            type="button"
            class="btn-page-nav"
            :disabled="pagination.current_page <= 1"
            @click="fetchUsers(pagination.current_page - 1)"
            aria-label="Previous Page"
          >
            <ChevronLeft :size="14" />
            <span>Previous</span>
          </button>

          <span class="page-indicator">
            Page {{ pagination.current_page }} of {{ pagination.last_page }}
          </span>

          <button
            type="button"
            class="btn-page-nav"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="fetchUsers(pagination.current_page + 1)"
            aria-label="Next Page"
          >
            <span>Next</span>
            <ChevronRight :size="14" />
          </button>
        </div>
      </div>
    </div>

    <!-- Add User Modal -->
    <transition name="modal-fade">
      <div v-if="showAddModal" class="modal-backdrop">
        <div class="modal-container">
          <!-- Modal Header -->
          <div class="modal-header">
            <div>
              <div class="header-badge mb-1">
                <UserPlus :size="13" />
                <span>Onboarding</span>
              </div>
              <h2 class="modal-title">Add New User</h2>
              <p class="modal-subtitle">Register a new retail employee account in GrowHub.</p>
            </div>
            <button type="button" class="btn-close" @click="closeAddModal" aria-label="Close">
              <X :size="20" />
            </button>
          </div>

          <!-- Error Alert Banner -->
          <div v-if="errorMessage" class="error-banner m-3 mb-0">
            <AlertCircle :size="16" />
            <span>{{ errorMessage }}</span>
          </div>

          <!-- Modal Form -->
          <form @submit.prevent="submitAddUser" class="modal-form">
            <div class="form-grid">
              <!-- Employee ID (NIP) -->
              <div class="form-group">
                <label class="form-label" for="add-user-nip">
                  Employee ID (NIP) <span class="text-red">*</span>
                </label>
                <input
                  id="add-user-nip"
                  v-model="formAdd.nip"
                  type="text"
                  class="form-control"
                  placeholder="e.g. 202301015"
                  required
                  maxlength="12"
                />
                <span class="form-hint">
                  Numbers only (6-12 digits). Used as default username & initial password.
                </span>
              </div>

              <!-- Full Name -->
              <div class="form-group">
                <label class="form-label" for="add-user-name">
                  Full Name <span class="text-red">*</span>
                </label>
                <input
                  id="add-user-name"
                  v-model="formAdd.name"
                  type="text"
                  class="form-control"
                  placeholder="e.g. Budi Santoso"
                  required
                  maxlength="150"
                />
              </div>

              <!-- Email Address -->
              <div class="form-group">
                <label class="form-label" for="add-user-email">
                  Email Address
                </label>
                <input
                  id="add-user-email"
                  v-model="formAdd.email"
                  type="email"
                  class="form-control"
                  placeholder="e.g. budi.santoso@growhub.retail"
                  maxlength="150"
                />
              </div>

              <!-- Role Selector -->
              <div class="form-group form-col-full">
                <label class="form-label">
                  System Role <span class="text-red">*</span>
                </label>
                <div class="role-selector-grid">
                  <label
                    class="role-option-card"
                    :class="{ 'selected': formAdd.role === 'employee' }"
                  >
                    <input
                      v-model="formAdd.role"
                      type="radio"
                      value="employee"
                      name="add-role"
                      class="sr-only"
                    />
                    <div class="role-option-header">
                      <User :size="16" />
                      <span class="bold">Employee</span>
                    </div>
                    <span class="role-option-desc">Frontline contributor</span>
                  </label>

                  <label
                    class="role-option-card"
                    :class="{ 'selected': formAdd.role === 'reviewer' }"
                  >
                    <input
                      v-model="formAdd.role"
                      type="radio"
                      value="reviewer"
                      name="add-role"
                      class="sr-only"
                    />
                    <div class="role-option-header">
                      <ShieldCheck :size="16" />
                      <span class="bold">Reviewer</span>
                    </div>
                    <span class="role-option-desc">Validates content</span>
                  </label>

                  <label
                    class="role-option-card"
                    :class="{ 'selected': formAdd.role === 'admin' }"
                  >
                    <input
                      v-model="formAdd.role"
                      type="radio"
                      value="admin"
                      name="add-role"
                      class="sr-only"
                    />
                    <div class="role-option-header">
                      <Award :size="16" />
                      <span class="bold">Admin</span>
                    </div>
                    <span class="role-option-desc">Full access manager</span>
                  </label>
                </div>
              </div>

              <!-- Department -->
              <div class="form-group">
                <label class="form-label" for="add-user-dept">Department</label>
                <select id="add-user-dept" v-model="formAdd.department" class="form-control">
                  <option value="Store Operations">Store Operations</option>
                  <option value="Merchandising">Merchandising</option>
                  <option value="Customer Service">Customer Service</option>
                  <option value="Human Capital">Human Capital</option>
                  <option value="Supply Chain">Supply Chain</option>
                  <option value="IT">IT</option>
                  <option value="Marketing">Marketing</option>
                  <option value="Finance">Finance</option>
                </select>
              </div>

              <!-- Position -->
              <div class="form-group">
                <label class="form-label" for="add-user-position">Position / Job Title</label>
                <input
                  id="add-user-position"
                  v-model="formAdd.position"
                  type="text"
                  class="form-control"
                  placeholder="e.g. Store Associate, Cashier, Team Lead"
                  maxlength="100"
                />
              </div>

              <!-- Location / Store Branch -->
              <div class="form-group">
                <label class="form-label" for="add-user-location">Branch / Location</label>
                <input
                  id="add-user-location"
                  v-model="formAdd.location"
                  type="text"
                  class="form-control"
                  placeholder="e.g. Store #104 - Jakarta, Central HQ"
                  maxlength="100"
                />
              </div>

              <!-- Initial Welcome Points -->
              <div class="form-group">
                <label class="form-label" for="add-user-points">
                  Initial Points Bonus
                </label>
                <div class="input-with-icon">
                  <Sparkles :size="16" class="input-icon text-amber" />
                  <input
                    id="add-user-points"
                    v-model.number="formAdd.points"
                    type="number"
                    min="0"
                    max="10000"
                    class="form-control with-icon"
                    placeholder="0"
                  />
                </div>
                <span class="form-hint">Award welcome points for joining the platform.</span>
              </div>

              <!-- Custom Password (Optional) -->
              <div class="form-group form-col-full">
                <label class="form-label" for="add-user-password">
                  Custom Password <span class="muted font-normal">(Optional)</span>
                </label>
                <input
                  id="add-user-password"
                  v-model="formAdd.password"
                  type="password"
                  class="form-control"
                  placeholder="Leave empty to use Employee NIP as default password"
                  minlength="4"
                />
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-outline"
                :disabled="submitting"
                @click="closeAddModal"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="submitting"
              >
                <span v-if="submitting" class="spinner-sm mr-2"></span>
                <UserPlus v-else :size="16" class="mr-1" />
                <span>{{ submitting ? 'Creating User...' : 'Create User' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Edit User Modal -->
    <transition name="modal-fade">
      <div v-if="showEditModal" class="modal-backdrop">
        <div class="modal-container">
          <!-- Modal Header -->
          <div class="modal-header">
            <div>
              <div class="header-badge mb-1">
                <Pencil :size="13" />
                <span>Edit Profile</span>
              </div>
              <h2 class="modal-title">Edit User (NIP: {{ formEdit.nip }})</h2>
              <p class="modal-subtitle">Update employee credentials, role, and department information.</p>
            </div>
            <button type="button" class="btn-close" @click="closeEditModal" aria-label="Close">
              <X :size="20" />
            </button>
          </div>

          <!-- Error Alert Banner -->
          <div v-if="errorMessage" class="error-banner m-3 mb-0">
            <AlertCircle :size="16" />
            <span>{{ errorMessage }}</span>
          </div>

          <!-- Modal Form -->
          <form @submit.prevent="submitEditUser" class="modal-form">
            <div class="form-grid">
              <!-- Employee ID (NIP - Readonly) -->
              <div class="form-group">
                <label class="form-label">Employee ID (NIP)</label>
                <input
                  :value="formEdit.nip"
                  type="text"
                  class="form-control bg-subtle"
                  disabled
                  readonly
                />
                <span class="form-hint">Employee ID cannot be modified once created.</span>
              </div>

              <!-- Full Name -->
              <div class="form-group">
                <label class="form-label" for="edit-user-name">
                  Full Name <span class="text-red">*</span>
                </label>
                <input
                  id="edit-user-name"
                  v-model="formEdit.name"
                  type="text"
                  class="form-control"
                  required
                  maxlength="150"
                />
              </div>

              <!-- Email Address -->
              <div class="form-group">
                <label class="form-label" for="edit-user-email">Email Address</label>
                <input
                  id="edit-user-email"
                  v-model="formEdit.email"
                  type="email"
                  class="form-control"
                  maxlength="150"
                />
              </div>

              <!-- Account Status Toggle -->
              <div class="form-group">
                <label class="form-label" for="edit-user-status">Account Status</label>
                <select id="edit-user-status" v-model="formEdit.is_active" class="form-control">
                  <option :value="true">Active</option>
                  <option :value="false">Inactive / Suspended</option>
                </select>
              </div>

              <!-- Role Selector -->
              <div class="form-group form-col-full">
                <label class="form-label">System Role <span class="text-red">*</span></label>
                <div class="role-selector-grid">
                  <label class="role-option-card" :class="{ 'selected': formEdit.role === 'employee' }">
                    <input v-model="formEdit.role" type="radio" value="employee" name="edit-role" class="sr-only" />
                    <div class="role-option-header"><User :size="16" /><span class="bold">Employee</span></div>
                    <span class="role-option-desc">Frontline contributor</span>
                  </label>
                  <label class="role-option-card" :class="{ 'selected': formEdit.role === 'reviewer' }">
                    <input v-model="formEdit.role" type="radio" value="reviewer" name="edit-role" class="sr-only" />
                    <div class="role-option-header"><ShieldCheck :size="16" /><span class="bold">Reviewer</span></div>
                    <span class="role-option-desc">Validates content</span>
                  </label>
                  <label class="role-option-card" :class="{ 'selected': formEdit.role === 'admin' }">
                    <input v-model="formEdit.role" type="radio" value="admin" name="edit-role" class="sr-only" />
                    <div class="role-option-header"><Award :size="16" /><span class="bold">Admin</span></div>
                    <span class="role-option-desc">Full access manager</span>
                  </label>
                </div>
              </div>

              <!-- Department -->
              <div class="form-group">
                <label class="form-label" for="edit-user-dept">Department</label>
                <select id="edit-user-dept" v-model="formEdit.department" class="form-control">
                  <option value="Store Operations">Store Operations</option>
                  <option value="Merchandising">Merchandising</option>
                  <option value="Customer Service">Customer Service</option>
                  <option value="Human Capital">Human Capital</option>
                  <option value="Supply Chain">Supply Chain</option>
                  <option value="IT">IT</option>
                  <option value="Marketing">Marketing</option>
                  <option value="Finance">Finance</option>
                </select>
              </div>

              <!-- Position -->
              <div class="form-group">
                <label class="form-label" for="edit-user-position">Position / Job Title</label>
                <input
                  id="edit-user-position"
                  v-model="formEdit.position"
                  type="text"
                  class="form-control"
                  maxlength="100"
                />
              </div>

              <!-- Location / Store Branch -->
              <div class="form-group">
                <label class="form-label" for="edit-user-location">Branch / Location</label>
                <input
                  id="edit-user-location"
                  v-model="formEdit.location"
                  type="text"
                  class="form-control"
                  maxlength="100"
                />
              </div>

              <!-- Points Adjustment -->
              <div class="form-group">
                <label class="form-label" for="edit-user-points">Gamification Points</label>
                <div class="input-with-icon">
                  <Sparkles :size="16" class="input-icon text-amber" />
                  <input
                    id="edit-user-points"
                    v-model.number="formEdit.points"
                    type="number"
                    min="0"
                    max="100000"
                    class="form-control with-icon"
                  />
                </div>
              </div>

              <!-- Reset Password -->
              <div class="form-group form-col-full">
                <div class="row-between mb-1">
                  <label class="form-label mb-0" for="edit-user-password">
                    Change Password <span class="muted font-normal">(Leave blank to keep current)</span>
                  </label>
                  <button
                    type="button"
                    class="btn-text-action text-primary small bold"
                    @click="formEdit.password = formEdit.nip"
                  >
                    Set ke NIP ({{ formEdit.nip }})
                  </button>
                </div>
                <input
                  id="edit-user-password"
                  v-model="formEdit.password"
                  type="password"
                  class="form-control"
                  placeholder="Enter new password (optional)"
                  minlength="4"
                />
                <span class="form-hint text-amber-dark">
                  <KeyRound :size="12" /> Mengubah password user di sini akan otomatis mewajibkan user mengganti password saat login berikutnya.
                </span>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-outline"
                :disabled="submitting"
                @click="closeEditModal"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="submitting"
              >
                <span v-if="submitting" class="spinner-sm mr-2"></span>
                <Pencil v-else :size="16" class="mr-1" />
                <span>{{ submitting ? 'Saving Changes...' : 'Save Changes' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Delete Confirmation Modal -->
    <transition name="modal-fade">
      <div v-if="showDeleteModal" class="modal-backdrop" @click.self="showDeleteModal = false">
        <div class="modal-container modal-sm">
          <div class="confirmation-modal-body">
            <div class="delete-icon-circle">
              <AlertTriangle :size="30" />
            </div>
            <h3 class="modal-title">Delete Employee Account?</h3>
            <p class="muted small">
              Are you sure you want to permanently delete <strong class="text-dark">{{ userToDelete?.name }}</strong> (NIP: <span class="user-nip-badge">{{ userToDelete?.nip }}</span>)? This action cannot be undone.
            </p>
            <div class="modal-footer justify-center">
              <button
                type="button"
                class="btn btn-outline"
                :disabled="submitting"
                @click="showDeleteModal = false"
              >
                Cancel
              </button>
              <button
                type="button"
                class="btn btn-danger"
                :disabled="submitting"
                @click="confirmDeleteUser"
              >
                <span v-if="submitting" class="spinner-sm mr-2"></span>
                <Trash2 v-else :size="16" class="mr-1" />
                <span>{{ submitting ? 'Deleting...' : 'Delete User' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Reset Password Confirmation Modal -->
    <transition name="modal-fade">
      <div v-if="showResetModal" class="modal-backdrop" @click.self="showResetModal = false">
        <div class="modal-container modal-sm">
          <div class="confirmation-modal-body">
            <div class="reset-icon-circle">
              <KeyRound :size="30" />
            </div>
            <h3 class="modal-title">Reset Password Employee?</h3>
            <p class="muted small">
              Password untuk <strong>{{ userToReset?.name }}</strong> (NIP: <span class="user-nip-badge">{{ userToReset?.nip }}</span>) akan direset kembali ke default NIP (<span class="code-pill">{{ userToReset?.nip }}</span>).
            </p>
            <div class="alert-box-info">
              <ShieldCheck :size="16" class="text-amber flex-shrink-0" />
              <span class="small">
                User akan langsung diarahkan untuk mengganti password barunya saat login kembali.
              </span>
            </div>
            <div class="modal-footer justify-center">
              <button
                type="button"
                class="btn btn-outline"
                :disabled="submitting"
                @click="showResetModal = false"
              >
                Cancel
              </button>
              <button
                type="button"
                class="btn btn-warning"
                :disabled="submitting"
                @click="confirmResetPassword"
              >
                <span v-if="submitting" class="spinner-sm mr-2"></span>
                <KeyRound v-else :size="16" class="mr-1" />
                <span>{{ submitting ? 'Resetting...' : 'Confirm Reset Password' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import {
  Users, UserPlus, UserCheck, ShieldCheck, Award, User,
  Search, X, Sparkles, Flame, AlertCircle, Pencil, Trash2,
  AlertTriangle, ChevronLeft, ChevronRight, KeyRound
} from 'lucide-vue-next'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const authStore = useAuthStore()
const toast = useToastStore()

const users = ref([])
const departments = ref([])
const stats = reactive({
  total_users: 0,
  total_employees: 0,
  total_reviewers: 0,
  total_admins: 0,
})

const loading = ref(false)
const submitting = ref(false)
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showResetModal = ref(false)
const userToDelete = ref(null)
const userToReset = ref(null)
const errorMessage = ref('')

const searchQuery = ref('')
const selectedRole = ref('')
const selectedDept = ref('')

// Set pagination default to 10 per page
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 10,
  from: 0,
  to: 0,
})

const formAdd = reactive({
  nip: '',
  name: '',
  email: '',
  role: 'employee',
  department: 'Store Operations',
  position: 'Store Associate',
  location: 'Retail Store',
  points: 0,
  password: '',
})

const formEdit = reactive({
  id: null,
  nip: '',
  name: '',
  email: '',
  role: 'employee',
  department: 'Store Operations',
  position: '',
  location: '',
  points: 0,
  is_active: true,
  password: '',
})

let debounceTimer = null
const debouncedFetch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchUsers(1)
  }, 300)
}

const fetchUsers = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: 10,
      q: searchQuery.value || undefined,
      role: selectedRole.value || undefined,
      department: selectedDept.value || undefined,
    }

    const res = await api.get('/admin/users', { params })
    const data = res.data

    users.value = data.users.data || []
    departments.value = data.departments || []

    pagination.current_page = data.users.current_page
    pagination.last_page = data.users.last_page
    pagination.total = data.users.total
    pagination.per_page = data.users.per_page
    pagination.from = data.users.from
    pagination.to = data.users.to

    if (data.stats) {
      Object.assign(stats, data.stats)
    }
  } catch (err) {
    toast.error('Failed to load user directory.')
    console.error(err)
  } finally {
    loading.value = false
  }
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedRole.value = ''
  selectedDept.value = ''
  fetchUsers(1)
}

// Add User Modal Handlers
const openAddModal = () => {
  errorMessage.value = ''
  formAdd.nip = ''
  formAdd.name = ''
  formAdd.email = ''
  formAdd.role = 'employee'
  formAdd.department = 'Store Operations'
  formAdd.position = 'Store Associate'
  formAdd.location = 'Retail Store'
  formAdd.points = 0
  formAdd.password = ''
  showAddModal.value = true
}

const closeAddModal = () => {
  if (!submitting.value) {
    showAddModal.value = false
  }
}

const submitAddUser = async () => {
  errorMessage.value = ''

  if (!/^[0-9]{6,12}$/.test(formAdd.nip)) {
    errorMessage.value = 'Employee ID (NIP) must consist of 6 to 12 digits.'
    return
  }

  if (!formAdd.name.trim()) {
    errorMessage.value = 'Full Name is required.'
    return
  }

  submitting.value = true
  try {
    const payload = {
      nip: formAdd.nip.trim(),
      name: formAdd.name.trim(),
      email: formAdd.email.trim() || null,
      role: formAdd.role,
      department: formAdd.department,
      position: formAdd.position.trim(),
      location: formAdd.location.trim(),
      points: Number(formAdd.points) || 0,
      password: formAdd.password ? formAdd.password.trim() : undefined,
    }

    const res = await api.post('/admin/users', payload)
    toast.success(res.data.message || `User '${formAdd.name}' created successfully!`)
    showAddModal.value = false
    fetchUsers(1)
  } catch (err) {
    const errors = err.response?.data?.errors
    if (errors) {
      const firstKey = Object.keys(errors)[0]
      errorMessage.value = errors[firstKey][0]
    } else {
      errorMessage.value = err.response?.data?.message || 'Failed to create user. Please check your inputs.'
    }
  } finally {
    submitting.value = false
  }
}

// Edit User Modal Handlers
const openEditModal = (user) => {
  errorMessage.value = ''
  formEdit.id = user.id
  formEdit.nip = user.nip
  formEdit.name = user.name
  formEdit.email = user.email || ''
  formEdit.role = user.role || 'employee'
  formEdit.department = user.department || 'Store Operations'
  formEdit.position = user.position || ''
  formEdit.location = user.location || ''
  formEdit.points = user.points || 0
  formEdit.is_active = user.is_active !== false
  formEdit.password = ''
  showEditModal.value = true
}

const closeEditModal = () => {
  if (!submitting.value) {
    showEditModal.value = false
  }
}

const submitEditUser = async () => {
  errorMessage.value = ''

  if (!formEdit.name.trim()) {
    errorMessage.value = 'Full Name is required.'
    return
  }

  submitting.value = true
  try {
    const payload = {
      name: formEdit.name.trim(),
      email: formEdit.email.trim() || null,
      role: formEdit.role,
      department: formEdit.department,
      position: formEdit.position.trim(),
      location: formEdit.location.trim(),
      points: Number(formEdit.points) || 0,
      is_active: formEdit.is_active,
      password: formEdit.password ? formEdit.password.trim() : undefined,
    }

    const res = await api.put(`/admin/users/${formEdit.id}`, payload)
    toast.success(res.data.message || `User '${formEdit.name}' updated successfully!`)
    showEditModal.value = false
    fetchUsers(pagination.current_page)
  } catch (err) {
    const errors = err.response?.data?.errors
    if (errors) {
      const firstKey = Object.keys(errors)[0]
      errorMessage.value = errors[firstKey][0]
    } else {
      errorMessage.value = err.response?.data?.message || 'Failed to update user.'
    }
  } finally {
    submitting.value = false
  }
}

// Delete User Handlers
const openDeleteModal = (user) => {
  if (user.id === authStore.user?.id) {
    toast.error('You cannot delete your own account.')
    return
  }
  userToDelete.value = user
  showDeleteModal.value = true
}

const confirmDeleteUser = async () => {
  if (!userToDelete.value) return
  submitting.value = true
  try {
    const res = await api.delete(`/admin/users/${userToDelete.value.id}`)
    toast.success(res.data.message || 'User deleted successfully!')
    showDeleteModal.value = false
    userToDelete.value = null

    // If last user on current page deleted, decrement page
    const targetPage = (users.value.length === 1 && pagination.current_page > 1)
      ? pagination.current_page - 1
      : pagination.current_page
    fetchUsers(targetPage)
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to delete user.')
  } finally {
    submitting.value = false
  }
}

// Reset Password Handlers
const openResetModal = (user) => {
  userToReset.value = user
  showResetModal.value = true
}

const confirmResetPassword = async () => {
  if (!userToReset.value) return
  submitting.value = true
  try {
    const res = await api.post(`/admin/users/${userToReset.value.id}/reset-password`)
    toast.success(res.data.message || 'Password berhasil direset ke default (NIP).')
    showResetModal.value = false
    userToReset.value = null
    fetchUsers(pagination.current_page)
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal mereset password.')
  } finally {
    submitting.value = false
  }
}

const getInitials = (name) => {
  if (!name) return 'U'
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(w => w[0].toUpperCase())
    .join('')
}

const formatNumber = (num) => {
  return (num || 0).toLocaleString()
}

const formatRole = (role) => {
  if (!role) return 'Employee'
  return role.charAt(0).toUpperCase() + role.slice(1)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

onMounted(() => {
  fetchUsers(1)
})
</script>

<style scoped src="@/styles/features/admin-users.css"></style>
