import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class AdminApiService extends ChangeNotifier {
  static const String baseUrl = 'https://kicctest.org/api/v1/admin';
  String? _token;

  String? get token => _token;
  bool get isLoggedIn => _token != null;

  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  Future<void> login(String email, String password) async {
    final res = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'email': email, 'password': password, 'admin_type': 'kicc'}),
    );
    if (res.statusCode == 200) {
      _token = jsonDecode(res.body)['token'];
      notifyListeners();
    } else {
      throw Exception('Admin login failed');
    }
  }

  Future<Map<String, dynamic>> getDashboard() async {
    final res = await http.get(Uri.parse('$baseUrl/dashboard'), headers: _headers);
    return jsonDecode(res.body);
  }

  Future<List<dynamic>> getAgents({String status = 'pending'}) async {
    final res = await http.get(Uri.parse('$baseUrl/agents?status=$status'), headers: _headers);
    return jsonDecode(res.body)['data'] ?? [];
  }

  Future<void> approveAgent(int id, {double commissionRate = 5.0}) async {
    await http.post(Uri.parse('$baseUrl/agents/$id/approve'), headers: _headers,
        body: jsonEncode({'commission_rate': commissionRate}));
    notifyListeners();
  }

  Future<List<dynamic>> getOrders({String? status}) async {
    final uri = Uri.parse('$baseUrl/orders').replace(queryParameters: status != null ? {'status': status} : {});
    final res = await http.get(uri, headers: _headers);
    return jsonDecode(res.body)['data'] ?? [];
  }

  Future<List<dynamic>> getReviews({String status = 'pending'}) async {
    final res = await http.get(Uri.parse('$baseUrl/reviews?status=$status'), headers: _headers);
    return jsonDecode(res.body)['data'] ?? [];
  }

  Future<void> approveReview(int id) async {
    await http.post(Uri.parse('$baseUrl/reviews/$id/approve'), headers: _headers);
    notifyListeners();
  }

  Future<Map<String, dynamic>> getAnalytics({String period = 'month'}) async {
    final res = await http.get(Uri.parse('$baseUrl/analytics?period=$period'), headers: _headers);
    return jsonDecode(res.body);
  }

  Future<List<dynamic>> getEnquiries({String status = 'submitted'}) async {
    final res = await http.get(Uri.parse('$baseUrl/enquiries?status=$status'), headers: _headers);
    return jsonDecode(res.body)['data'] ?? [];
  }

  Future<void> approveEnquiry(int id, String status) async {
    await http.post(Uri.parse('$baseUrl/enquiries/$id/status'), headers: _headers,
        body: jsonEncode({'status': status}));
    notifyListeners();
  }
}