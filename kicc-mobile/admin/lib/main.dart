import "package:sentry_flutter/sentry_flutter.dart";
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'screens/login_screen.dart';
import 'screens/dashboard_screen.dart';
import 'screens/agents_screen.dart';
import 'screens/orders_screen.dart';
import 'screens/reviews_screen.dart';
import 'screens/products_screen.dart';
import 'screens/analytics_screen.dart';
import 'screens/enquiries_screen.dart';
import 'screens/commissions_screen.dart';
import 'screens/coupons_screen.dart';
import 'screens/venues_screen.dart';
import 'screens/counties_screen.dart';
import 'services/admin_api_service.dart';

Future<void> main() async {
  await SentryFlutter.init(
    (options) {
      options.dsn = "https://349f99bb1709a89d8e0dfa0f6250e1f2@o4511927041916928.ingest.de.sentry.io/4511927067476048";
    },
    appRunner: () {
      runApp(
        MultiProvider(
          providers: [
            ChangeNotifierProvider(create: (_) => AdminApiService()),
          ],
          child: const KiccAdminApp(),
        ),
      );
    },
  );
}

class KiccAdminApp extends StatelessWidget {
  const KiccAdminApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'KICC Admin',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF046BD2),
          primary: const Color(0xFF046BD2),
        ),
        useMaterial3: true,
      ),
      initialRoute: '/login',
      routes: {
        '/login': (context) => const LoginScreen(),
        '/dashboard': (context) => const DashboardScreen(),
        '/agents': (context) => const AgentsScreen(),
        '/orders': (context) => const OrdersScreen(),
        '/reviews': (context) => const ReviewsScreen(),
        '/products': (context) => const ProductsScreen(),
        '/analytics': (context) => const AnalyticsScreen(),
        '/enquiries': (context) => const EnquiriesScreen(),
        '/commissions': (context) => const CommissionsScreen(),
        '/coupons': (context) => const CouponsScreen(),
        '/venues': (context) => const VenuesScreen(),
        '/counties': (context) => const CountiesScreen(),
      },
    );
  }
}