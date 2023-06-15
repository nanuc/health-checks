<?php

namespace Nanuc\HealthChecks\HealthChecks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Spatie\Regex\Regex;
use Spatie\Ssh\Ssh;

class UsedDiskSpaceOnRemoteSystemCheck extends Check
{
    protected int $warningThreshold = 70;

    protected int $errorThreshold = 90;

    protected string $user = 'root';
    protected string $host = 'localhost';
    protected int $port = 22;

    protected ?string $filesystemName = null;

    public function filesystemName(string $filesystemName): self
    {
        $this->filesystemName = $filesystemName;

        return $this;
    }

    public function user($user = null)
    {
        $this->user = $user;

        return $this;
    }

    public function host(string $host)
    {
        $this->host = $host;

        return $this;
    }

    public function port(string $port)
    {
        $this->port = $port;

        return $this;
    }

    public function warnWhenUsedSpaceIsAbovePercentage(int $percentage): self
    {
        $this->warningThreshold = $percentage;

        return $this;
    }

    public function failWhenUsedSpaceIsAbovePercentage(int $percentage): self
    {
        $this->errorThreshold = $percentage;

        return $this;
    }

    public function run(): Result
    {
        $diskSpaceUsedPercentage = $this->getDiskUsagePercentage();

        $result = Result::make()
            ->meta(['disk_space_used_percentage' => $diskSpaceUsedPercentage])
            ->shortSummary($diskSpaceUsedPercentage.'%');

        if ($diskSpaceUsedPercentage > $this->errorThreshold) {
            return $result->failed("The disk is almost full ({$diskSpaceUsedPercentage}% used).");
        }

        if ($diskSpaceUsedPercentage > $this->warningThreshold) {
            return $result->warning("The disk is almost full ({$diskSpaceUsedPercentage}% used).");
        }

        return $result->ok();
    }

    protected function getDiskUsagePercentage(): int
    {
        $process = Ssh::create($this->user, $this->host, $this->port)
            ->execute('df -P '.($this->filesystemName ?: '.'));

        $output = $process->getOutput();

        return (int) Regex::match('/(\d*)%/', $output)->group(1);
    }
}