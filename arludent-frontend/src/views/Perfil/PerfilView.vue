<template>
  <DashboardLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Profile Header -->
      <Card>
        <div class="flex items-center space-x-6">
          <div class="w-24 h-24 bg-primary-100 rounded-full flex items-center justify-center">
            <User class="w-12 h-12 text-primary-600" />
          </div>
          <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900">{{ user?.username }}</h2>
            <p class="text-gray-600">{{ user?.correo }}</p>
            <div class="flex items-center space-x-4 mt-2">
              <span class="badge badge-success">{{ user?.estado }}</span>
              <span v-if="isMFAEnabled" class="badge badge-info flex items-center space-x-1">
                <ShieldCheck class="w-4 h-4" />
                <span>2FA Activo</span>
              </span>
            </div>
          </div>
        </div>
      </Card>

      <!-- Update Profile -->
      <Card title="Actualizar Perfil">
        <form @submit.prevent="handleUpdateProfile" class="space-y-4">
          <InputField
            v-model="profileForm.username"
            label="Nombre de Usuario"
            type="text"
            :error="errors.username"
            :icon="User"
          />

          <InputField
            v-model="profileForm.telefono"
            label="Teléfono"
            type="tel"
            :error="errors.telefono"
            :icon="Phone"
          />

          <Button
            type="submit"
            variant="primary"
            :loading="isUpdating"
            fullWidth
          >
            Actualizar Perfil
          </Button>
        </form>
      </Card>

      <!-- Change Password -->
      <Card title="Cambiar Contraseña">
        <form @submit.prevent="handleChangePassword" class="space-y-4">
          <InputField
            v-model="passwordForm.password_actual"
            label="Contraseña Actual"
            type="password"
            :error="errors.password_actual"
            :icon="Lock"
          />

          <InputField
            v-model="passwordForm.password"
            label="Nueva Contraseña"
            type="password"
            :error="errors.password"
            :icon="Lock"
          />

          <InputField
            v-model="passwordForm.password_confirmation"
            label="Confirmar Nueva Contraseña"
            type="password"
            :error="errors.password_confirmation"
            :icon="Lock"
          />

          <Button
            type="submit"
            variant="primary"
            :loading="isChangingPassword"
            fullWidth
          >
            Cambiar Contraseña
          </Button>
        </form>
      </Card>

      <!-- MFA Settings -->
      <Card title="Autenticación de Dos Factores">
        <div class="space-y-4">
          <p class="text-sm text-gray-600">
            La autenticación de dos factores agrega una capa adicional de seguridad a tu cuenta.
          </p>

          <div v-if="isMFAEnabled" class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <ShieldCheck class="w-6 h-6 text-green-600" />
                <div>
                  <p class="font-medium text-green-900">2FA Habilitado</p>
                  <p class="text-sm text-green-700">Tu cuenta está protegida</p>
                </div>
              </div>
              <Button
                @click="showDisableMFAModal = true"
                variant="danger"
                size="sm"
              >
                Deshabilitar
              </Button>
            </div>
          </div>

          <div v-else class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <AlertCircle class="w-6 h-6 text-yellow-600" />
                <div>
                  <p class="font-medium text-yellow-900">2FA Deshabilitado</p>
                  <p class="text-sm text-yellow-700">Recomendamos habilitar esta función</p>
                </div>
              </div>
              <Button
                @click="handleEnableMFA"
                variant="success"
                size="sm"
              >
                Habilitar
              </Button>
            </div>
          </div>
        </div>
      </Card>

      <!-- MFA Confirmation Modal -->
      <Modal
        :show="showMFAConfirmModal"
        title="Confirmar Habilitación de 2FA"
        @close="showMFAConfirmModal = false"
      >
        <div class="space-y-4">
          <p class="text-sm text-gray-600">
            Ingresa el código de 6 dígitos de tu aplicación de autenticación para confirmar.
          </p>

          <InputField
            v-model="mfaCode"
            label="Código de Verificación"
            type="text"
            placeholder="000000"
            maxlength="6"
          />
        </div>

        <template #footer>
          <Button
            @click="showMFAConfirmModal = false"
            variant="secondary"
          >
            Cancelar
          </Button>
          <Button
            @click="handleConfirmMFA"
            variant="primary"
            :loading="isConfirmingMFA"
          >
            Confirmar
          </Button>
        </template>
      </Modal>

      <!-- Disable MFA Modal -->
      <Modal
        :show="showDisableMFAModal"
        title="Deshabilitar 2FA"
        @close="showDisableMFAModal = false"
      >
        <div class="space-y-4">
          <p class="text-sm text-gray-600">
            Ingresa el código de 6 dígitos de tu aplicación de autenticación para deshabilitar 2FA.
          </p>

          <InputField
            v-model="mfaCode"
            label="Código de Verificación"
            type="text"
            placeholder="000000"
            maxlength="6"
          />
        </div>

        <template #footer>
          <Button
            @click="showDisableMFAModal = false"
            variant="secondary"
          >
            Cancelar
          </Button>
          <Button
            @click="handleDisableMFA"
            variant="danger"
            :loading="isDisablingMFA"
          >
            Deshabilitar
          </Button>
        </template>
      </Modal>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useUserStore } from '@/stores/userStore'
import { useMFA } from '@/composables/useMFA'
import { useValidation } from '@/composables/useValidation'
import { useNotification } from '@/composables/useNotification'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/common/Card.vue'
import InputField from '@/components/common/InputField.vue'
import Button from '@/components/common/Button.vue'
import Modal from '@/components/common/Modal.vue'
import {
  User,
  Phone,
  Lock,
  ShieldCheck,
  AlertCircle
} from 'lucide-vue-next'

const authStore = useAuthStore()
const userStore = useUserStore()
const { enableMFA, confirmMFA, disableMFA } = useMFA()
const { errors, clearErrors, setErrors } = useValidation()
const { showSuccess } = useNotification()

const user = computed(() => authStore.user)
const isMFAEnabled = computed(() => authStore.isMFAEnabled)

const profileForm = reactive({
  username: '',
  telefono: ''
})

const passwordForm = reactive({
  password_actual: '',
  password: '',
  password_confirmation: ''
})

const isUpdating = ref(false)
const isChangingPassword = ref(false)
const showMFAConfirmModal = ref(false)
const showDisableMFAModal = ref(false)
const mfaCode = ref('')
const isConfirmingMFA = ref(false)
const isDisablingMFA = ref(false)

onMounted(() => {
  if (user.value) {
    profileForm.username = user.value.username || ''
    profileForm.telefono = user.value.telefono || ''
  }
})

const handleUpdateProfile = async () => {
  clearErrors()

  try {
    isUpdating.value = true
    await userStore.updateProfile(profileForm)
    await authStore.fetchUser()
    showSuccess('Perfil actualizado correctamente')
  } catch (error: any) {
    if (error.response?.status === 422) {
      setErrors(error.response.data.errors)
    }
  } finally {
    isUpdating.value = false
  }
}

const handleChangePassword = async () => {
  clearErrors()

  try {
    isChangingPassword.value = true
    await authStore.changePassword(passwordForm)
    passwordForm.password_actual = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    showSuccess('Contraseña cambiada correctamente')
  } catch (error: any) {
    if (error.response?.status === 422) {
      setErrors(error.response.data.errors)
    }
  } finally {
    isChangingPassword.value = false
  }
}

const handleEnableMFA = async () => {
  try {
    await enableMFA()
    showMFAConfirmModal.value = true
  } catch (error) {
    // Error handled by composable
  }
}

const handleConfirmMFA = async () => {
  try {
    isConfirmingMFA.value = true
    await confirmMFA(mfaCode.value)
    showMFAConfirmModal.value = false
    mfaCode.value = ''
    await authStore.fetchUser()
  } catch (error) {
    // Error handled by composable
  } finally {
    isConfirmingMFA.value = false
  }
}

const handleDisableMFA = async () => {
  try {
    isDisablingMFA.value = true
    await disableMFA(mfaCode.value)
    showDisableMFAModal.value = false
    mfaCode.value = ''
    await authStore.fetchUser()
  } catch (error) {
    // Error handled by composable
  } finally {
    isDisablingMFA.value = false
  }
}
</script>
