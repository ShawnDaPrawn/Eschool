import 'package:eschool/utils/api.dart';

class PolicyRepository {
  Future<Map<String, dynamic>> status() async {
    final result = await Api.get(
      url: Api.parentPolicyAcceptance,
      useAuthToken: true,
    );

    return Map<String, dynamic>.from(result['data'] as Map);
  }

  Future<Map<String, dynamic>> accept({
    required String document,
    required String version,
  }) async {
    final result = await Api.post(
      body: {'document': document, 'version': version},
      url: Api.parentPolicyAcceptance,
      useAuthToken: true,
    );

    return Map<String, dynamic>.from(result['data'] as Map);
  }
}
