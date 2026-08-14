import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class ApiService extends ChangeNotifier {
  static const String baseUrl = 'https://kicctest.org/api/v1';
  String? _token;
  Map<String, dynamic>? _user;

  String? get token => _token;
  Map<String, dynamic>? get user => _user;
  bool get isLoggedIn => _token != null;

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  Future<void> login(String email, String password) async {
    final res = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'email': email, 'password': password}),
    );
    if (res.statusCode == 200) {
      final data = jsonDecode(res.body);
      _token = data['token'];
      _user = data['user'];
      notifyListeners();
    } else {
      throw Exception('Login failed');
    }
  }

  Future<List<dynamic>> getProducts({int page = 1, String? category, String? county, String? search}) async {
    final uri = Uri.parse('$baseUrl/products').replace(queryParameters: {
      'page': page.toString(),
      if (category != null) 'category': category,
      if (county != null) 'county': county,
      if (search != null) 'q': search,
    });
    final res = await http.get(uri, headers: _headers);
    return jsonDecode(res.body)['data'] ?? [];
  }

  Future<List<dynamic>> search(String query, {String? type, String? county}) async {
    final uri = Uri.parse('$baseUrl/search').replace(queryParameters: {
      'q': query,
      if (type != null) 'type': type,
      if (county != null) 'county': county,
    });
    final res = await http.get(uri, headers: _headers);
    return jsonDecode(res.body)['data'] ?? [];
  }

  Future<void> addToCart(int variantId, int quantity) async {
    await http.post(
      Uri.parse('$baseUrl/cart/add'),
      headers: _headers,
      body: jsonEncode({'variant_id': variantId, 'quantity': quantity}),
    );
    notifyListeners();
  }

  Future<Map<String, dynamic>> checkout(Map<String, dynamic> data) async {
    final res = await http.post(Uri.parse('$baseUrl/checkout'), headers: _headers, body: jsonEncode(data));
    return jsonDecode(res.body);
  }
}