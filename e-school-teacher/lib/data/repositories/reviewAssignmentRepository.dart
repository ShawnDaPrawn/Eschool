import 'package:eschool_teacher/data/models/reviewAssignmentssubmition.dart';
import 'package:eschool_teacher/utils/api.dart';

class ReviewAssignmentRepository {
  Future<List<ReviewAssignmentsSubmission>> fetchReviewAssignment({
    required int assignmetId,
  }) async {
    try {
      final body = {
        'assignment_id': assignmetId,
      };

      final result = await Api.get(
        url: Api.getReviewAssignment,
        useAuthToken: true,
        queryParameters: body,
      );

      return (result['data'] as List)
          .map(
            (reviewAssignment) => ReviewAssignmentsSubmission.fromJson(
              Map.from(reviewAssignment),
            ),
          )
          .toList();
    } catch (e) {
      throw ApiException(e.toString());
    }
  }

  Future<void> updateReviewAssignment({
    required int reviewAssignmetId,
    required int reviewAssignmentStatus,
    required int reviewAssignmentPoints,
    required String reviewAssignmentFeedBack,
  }) async {
    try {
      final body = {
        'assignment_submission_id': reviewAssignmetId,
        'status': reviewAssignmentStatus,
        'points': reviewAssignmentPoints,
        'feedback': reviewAssignmentFeedBack,
      };
      if (reviewAssignmentPoints == 0 || reviewAssignmentPoints == -1) {
        body.remove('points');
      }
      if (reviewAssignmentFeedBack.isEmpty) {
        body.remove('feedback');
      }
      await Api.post(
        body: body,
        url: Api.updateReviewAssignment,
        useAuthToken: true,
      );
    } catch (e) {
      throw ApiException(e.toString());
    }
  }
}
