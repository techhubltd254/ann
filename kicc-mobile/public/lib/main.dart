import "package:sentry_flutter/sentry_flutter.dart";
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'screens/home_screen.dart';
import 'screens/login_screen.dart';
import 'screens/search_screen.dart';
import 'screens/product_detail_screen.dart';
import 'screens/cart_screen.dart';
import 'screens/checkout_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/messages_screen.dart';
import 'screens/notifications_screen.dart';
import 'screens/tourism/guides_screen.dart';
import 'screens/tourism/rentals_screen.dart';
import 'screens/tourism/restaurants_screen.dart';
import 'screens/trade/agreements_screen.dart';
import 'services/api_service.dart';

Future<void> main() async {
  await SentryFlutter.init(
    (options) {
      options.dsn = "https://your-dsn@o0.ingest.sentry.io/0";
    },
    appRunner: () {
      runApp(
        MultiProvider(
          providers: [
            ChangeNotifierProvider(create: (_) => ApiService()),
          ],
          child: const KiccPublicApp(),
        ),
      );
    },
  );
}

class KiccPublicApp extends StatelessWidget {
  const KiccPublicApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'KICC Tourism',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF046BD2),
          primary: const Color(0xFF046BD2),
        ),
        useMaterial3: true,
        fontFamily: 'Inter',
      ),
      initialRoute: '/',
      routes: {
        '/': (context) => const HomeScreen(),
        '/login': (context) => const LoginScreen(),
        '/search': (context) => const SearchScreen(),
        '/cart': (context) => const CartScreen(),
        '/checkout': (context) => const CheckoutScreen(),
        '/profile': (context) => const ProfileScreen(),
        '/messages': (context) => const MessagesScreen(),
        '/notifications': (context) => const NotificationsScreen(),
        '/tourism/guides': (context) => const GuidesScreen(),
        '/tourism/rentals': (context) => const RentalsScreen(),
        '/tourism/restaurants': (context) => const RestaurantsScreen(),
        '/trade/agreements': (context) => const AgreementsScreen(),
        '/product': (context) => ProductDetailScreen(productId: ModalRoute.of(context)!.settings.arguments as int),
      },
    );
  }
}