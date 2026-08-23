import { Routes, Route, Navigate } from 'react-router-dom';
import AdminLayout from './components/AdminLayout';
import Overview from './pages/Overview';
import DetailsPage from './pages/Details';
import Analytics from './pages/Analytics';
import Attractions from './pages/Attractions';
import Hotels from './pages/Hotels';
import Products from './pages/Products';
import Marketplace from './pages/Marketplace';
import Video4D from './pages/Video4D';
import HeroVideos from './pages/HeroVideos';
import Gallery from './pages/Gallery';
import ContentCMS from './pages/ContentCMS';
import Orders from './pages/Orders';
import Pricing from './pages/Pricing';
import Ads from './pages/Ads';
import Packages from './pages/Packages';
import Sectors from './pages/Sectors';
import Reports from './pages/Reports';
import Settings from './pages/Settings';
import AuditLogs from './pages/AuditLogs';
import Login from './pages/Login';

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/" element={<AdminLayout />}>
        <Route index element={<Navigate to="/overview" replace />} />
        <Route path="overview" element={<Overview />} />
        <Route path="details" element={<DetailsPage />} />
        <Route path="analytics" element={<Analytics />} />
        <Route path="attractions" element={<Attractions />} />
        <Route path="hotels" element={<Hotels />} />
        <Route path="products" element={<Products />} />
        <Route path="marketplace" element={<Marketplace />} />
        <Route path="videos4d" element={<Video4D />} />
        <Route path="hero-videos" element={<HeroVideos />} />
        <Route path="gallery" element={<Gallery />} />
        <Route path="content" element={<ContentCMS />} />
        <Route path="orders" element={<Orders />} />
        <Route path="pricing" element={<Pricing />} />
        <Route path="ads" element={<Ads />} />
        <Route path="packages" element={<Packages />} />
        <Route path="sectors" element={<Sectors />} />
        <Route path="reports" element={<Reports />} />
        <Route path="settings" element={<Settings />} />
        <Route path="audit" element={<AuditLogs />} />
      </Route>
    </Routes>
  );
}