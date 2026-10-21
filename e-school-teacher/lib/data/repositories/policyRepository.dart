import 'package:eschool_teacher/utils/api.dart';

class PolicyRepository {
  /// True until this person has finished the first sign-in acceptance.
  static bool pending(Map<String, dynamic> status) {
    final navigateed = status['navigateed'];
    if (navigateed is! Map || navigateed['accepted'] != true) {
      return true;
    }
    final school = status['school'];
    return school is Map &&
        school['required'] == true &&
        school['accepted'] != true;
  }

  Future<Map<String, dynamic>> status() async {
    final result = await Api.get(
      url: Api.policyAcceptance,
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
      url: Api.policyAcceptance,
      useAuthToken: true,
    );

    return Map<String, dynamic>.from(result['data'] as Map);
  }
}
