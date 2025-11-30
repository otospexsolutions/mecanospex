import { useEffect } from 'react'
import { useTranslation } from 'react-i18next'
import { AuthProvider } from './features/auth'
import { AppRoutes } from './routes'
import { languages } from './lib/i18n'

function App() {
  const { i18n } = useTranslation()

  useEffect(() => {
    const currentLang = languages.find((l) => l.code === i18n.language)
    const dir = currentLang?.dir ?? 'ltr'
    document.documentElement.dir = dir
    document.documentElement.lang = i18n.language
  }, [i18n.language])

  return (
    <AuthProvider>
      <AppRoutes />
    </AuthProvider>
  )
}

export default App
