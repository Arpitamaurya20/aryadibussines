

<!-- ROW-1 -->
<h2 class="page-title text-primary">Leads Dashboard</h2>
<div class="row mt-5">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xl-12">
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-danger-gradient text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Enquiry Leads</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['Enquiries']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="saleschart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-secondary"><i
                                    class="fe fe-arrow-up-circle  text-secondary"></i> 5%</span>
                            Last week</span-->
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-blue text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">MSLV Leads</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['MSLVCourse']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="leadschart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-pink"><i
                                    class="fe fe-arrow-down-circle text-pink"></i> 0.75%</span>
                            Last 6 days</span-->
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-teal text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Scholarship Leads</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['scholarship_enquiries']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="profitchart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-green"><i
                                    class="fe fe-arrow-up-circle text-green"></i> 0.9%</span>
                            Last 9 days</span-->
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-azure text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Free Booklet Download</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['bookletdownloads']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="costchart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-warning"><i
                                    class="fe fe-arrow-up-circle text-warning"></i> 0.6%</span>
                            Last year</span-->
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-orange text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Mentorship Enquiries</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['MentorshipEnquiries']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="costchart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-warning"><i
                                    class="fe fe-arrow-up-circle text-warning"></i> 0.6%</span>
                            Last year</span-->
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-cyan text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Philosophy Webinar</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['TotalPhilosophyWebinar']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="costchart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-warning"><i
                                    class="fe fe-arrow-up-circle text-warning"></i> 0.6%</span>
                            Last year</span-->
                    </div>
                </div>
            </div>

          

        </div>
    </div>
</div>
<!-- ROW-1 END -->

<h2 class="page-title text-primary">Course Dashboard</h2>

<div class="row mt-5">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xl-12">
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-pink text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Total Courses</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['TotalCourses']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="leadschart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-pink"><i
                                    class="fe fe-arrow-down-circle text-pink"></i> 0.75%</span>
                            Last 6 days</span-->
                    </div>
                </div>
            </div>


            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-secondary text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Total Courses Books</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['TotalCoursesBooks']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="leadschart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-pink"><i
                                    class="fe fe-arrow-down-circle text-pink"></i> 0.75%</span>
                            Last 6 days</span-->
                    </div>
                </div>
            </div>


            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-cyan text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Total Offline Courses</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['TotalOfflineCourses']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="leadschart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-pink"><i
                                    class="fe fe-arrow-down-circle text-pink"></i> 0.75%</span>
                            Last 6 days</span-->
                    </div>
                </div>
            </div>


            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-indigo text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Total Online Courses</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['TotalOnlineCourses']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="leadschart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-pink"><i
                                    class="fe fe-arrow-down-circle text-pink"></i> 0.75%</span>
                            Last 6 days</span-->
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-info-gradient text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Total Main Courses</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['TotalMainCourses']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="leadschart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-pink"><i
                                    class="fe fe-arrow-down-circle text-pink"></i> 0.75%</span>
                            Last 6 days</span-->
                    </div>
                </div>
            </div>


            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-danger-gradient text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Total Optional Courses</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['TotalOptionalCourses']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="leadschart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-pink"><i
                                    class="fe fe-arrow-down-circle text-pink"></i> 0.75%</span>
                            Last 6 days</span-->
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-warning-gradient text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Total Module Courses</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['TotalModuleCourses']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="leadschart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-pink"><i
                                    class="fe fe-arrow-down-circle text-pink"></i> 0.75%</span>
                            Last 6 days</span-->
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-success-gradient text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Total Courses Codinator</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['TotalCoursesCodinator']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="leadschart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-pink"><i
                                    class="fe fe-arrow-down-circle text-pink"></i> 0.75%</span>
                            Last 6 days</span-->
                    </div>
                </div>
            </div>

            

        </div> <!-- row -->
    </div>
</div>

<h2 class="page-title text-primary">Content Dashboard</h2>

<div class="row mt-5">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xl-12">
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-indigo text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Total Current Affairs</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['TotalCurrentAffairs']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="saleschart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-secondary"><i
                                    class="fe fe-arrow-up-circle  text-secondary"></i> 5%</span>
                            Last week</span-->
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-green text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Total Monthly Affairs</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['TotalMonthlyAffairs']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="leadschart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-pink"><i
                                    class="fe fe-arrow-down-circle text-pink"></i> 0.75%</span>
                            Last 6 days</span-->
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-6 col-sm-12 col-xl-3">
                <div class="card overflow-hidden bg-gray text-light">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="mt-2">
                                <h6 class="">Total Magazines</h6>
                                <h2 class="mb-0 number-font"><?php echo $stats_array['TotalMagazine']; ?></h2>
                            </div>
                            <div class="ms-auto">
                                <div class="chart-wrapper mt-1">
                                    <canvas id="leadschart"
                                        class="h-8 w-9 chart-dropshadow"></canvas>
                                </div>
                            </div>
                        </div>
                        <!--span class="text-muted fs-12"><span class="text-pink"><i
                                    class="fe fe-arrow-down-circle text-pink"></i> 0.75%</span>
                            Last 6 days</span-->
                    </div>
                </div>
            </div>
        </div> <!-- row -->
    </div>
</div>
