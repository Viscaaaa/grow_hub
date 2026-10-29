import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import AppLayout from '@/components/layout/AppLayout.vue'
import LoginView from '@/views/LoginView.vue'
import HomeView from '@/views/HomeView.vue'
import DashboardView from '@/views/DashboardView.vue'
import FeedView from '@/views/FeedView.vue'
import ContributionIndexView from '@/views/my-contribution/Index.vue'
import CreateContentView from '@/views/my-contribution/CreateContentView.vue'
import ContributionEditView from '@/views/my-contribution/Edit.vue'
import ContentDetailView from '@/views/ContentDetailView.vue'
import SavedView from '@/views/SavedView.vue'
import LearningCenterView from '@/views/LearningCenterView.vue'
import CourseDetailView from '@/views/CourseDetailView.vue'
import CommunitiesView from '@/views/CommunitiesView.vue'
import CommunityDetailView from '@/views/CommunityDetailView.vue'
import LeaderboardView from '@/views/LeaderboardView.vue'
import NotificationsView from '@/views/NotificationsView.vue'
import HelpSupportView from '@/views/HelpSupportView.vue'
import ProfileView from '@/views/ProfileView.vue'
import ReviewPortalView from '@/views/ReviewPortalView.vue'
import AdminUsersView from '@/views/AdminUsersView.vue'
import AdminPointRulesView from '@/views/point-rules/AdminPointRulesView.vue'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { guest: true, title: 'Login – GrowHub' },
  },
  {
    path: '/',
    component: AppLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'home',
        component: HomeView,
        meta: { title: 'Home – GrowHub' },
      },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: DashboardView,
        meta: { title: 'My Dashboard & Gamification – GrowHub' },
      },
      {
        path: 'feed',
        name: 'feed',
        component: FeedView,
        meta: { title: 'Knowledge Feed – GrowHub' },
      },
      {
        path: 'create',
        name: 'create-content',
        component: CreateContentView,
        meta: { title: 'Create & Share Content – GrowHub' },
      },
      {
        path: 'content/:id',
        name: 'content-detail',
        component: ContentDetailView,
        meta: { title: 'Content Details – GrowHub' },
      },
      {
        path: 'contributions',
        name: 'contributions',
        component: ContributionIndexView,
        meta: { title: 'My Contributions – GrowHub' },
      },
      {
        path: 'contributions/create',
        name: 'contribution-create',
        component: CreateContentView,
        meta: { title: 'Create & Share Content – GrowHub' },
      },
      {
        path: 'contributions/:id/edit',
        name: 'contribution-edit',
        component: ContributionEditView,
        meta: { title: 'Edit Draft & Contribution – GrowHub' },
      },
      {
        path: 'saved',
        name: 'saved',
        component: SavedView,
        meta: { title: 'Saved Knowledge – GrowHub' },
      },
      {
        path: 'learning',
        name: 'learning',
        component: LearningCenterView,
        meta: { title: 'Learning Center – GrowHub' },
      },
      {
        path: 'learning/:slug',
        name: 'course-detail',
        component: CourseDetailView,
        meta: { title: 'Learning Module – GrowHub' },
      },
      {
        path: 'communities',
        name: 'communities',
        component: CommunitiesView,
        meta: { title: 'Retail Communities – GrowHub' },
      },
      {
        path: 'communities/:slug',
        name: 'community-detail',
        component: CommunityDetailView,
        meta: { title: 'Community Details – GrowHub' },
      },
      {
        path: 'leaderboard',
        name: 'leaderboard',
        component: LeaderboardView,
        meta: { title: 'Employee Leaderboard – GrowHub' },
      },
      {
        path: 'notifications',
        name: 'notifications',
        component: NotificationsView,
        meta: { title: 'Notifications – GrowHub' },
      },
      {
        path: 'help',
        name: 'help',
        component: HelpSupportView,
        meta: { title: 'Help & Support – GrowHub' },
      },
      {
        path: 'profile',
        name: 'profile-me',
        component: ProfileView,
        meta: { title: 'My Profile – GrowHub' },
      },
      {
        path: 'profile/:id',
        name: 'profile-user',
        component: ProfileView,
        meta: { title: 'Employee Profile – GrowHub' },
      },
      {
        path: 'review',
        name: 'review-portal',
        component: ReviewPortalView,
        meta: { title: 'Reviewer Validation Portal – GrowHub', requiresReviewer: true },
      },
      {
        path: 'admin/users',
        name: 'admin-users',
        component: AdminUsersView,
        meta: { title: 'User Management – GrowHub', requiresReviewer: true },
      },
      {
        path: 'admin/point-rules',
        name: 'admin-point-rules',
        component: AdminPointRulesView,
        meta: { title: 'Point System Settings – GrowHub', requiresAdmin: true },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  // Update document title for SEO and UX
  if (to.meta.title) {
    document.title = to.meta.title
  }

  // If user is authenticated but MUST change password (first login or admin reset),
  // they must remain on /login to set their new password before accessing the system.
  if (authStore.isAuthenticated && authStore.user?.must_change_password) {
    if (to.name !== 'login') {
      return next('/login')
    }
    return next()
  }

  if (to.meta.guest && authStore.isAuthenticated) {
    return next('/')
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next('/login')
  }

  if (to.meta.requiresReviewer && !authStore.isReviewer) {
    return next('/')
  }

  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    return next('/')
  }

  next()
})

export default router
